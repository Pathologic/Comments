<?php
/**
 * Class CommentedDocLister
 */
class RecentCommentsDocLister extends DocLister
{
    /**
     * Конструктор контроллеров DocLister
     *
     * @param DocumentParser $modx объект DocumentParser - основной класс MODX
     * @param mixed $cfg массив параметров сниппета
     * @param int $startTime время запуска сниппета
     * @throws Exception
     */
    public function __construct ($modx, $cfg = array(), $startTime = null)
    {
        $this->setTimeStart($startTime);

        if (extension_loaded('mbstring')) {
            mb_internal_encoding("UTF-8");
        } else {
            throw new Exception('Not found php extension mbstring');
        }

        if ($modx instanceof DocumentParser) {
            $this->modx = $modx;
            $this->setDebug(1);

            if (!is_array($cfg) || empty($cfg)) {
                $cfg = $this->modx->Event->params;
            }
        } else {
            throw new Exception('MODX var is not instaceof DocumentParser');
        }

        $this->FS = \Helpers\FS::getInstance();
        $this->config = new \Helpers\Config($cfg);

        if (isset($cfg['config'])) {
            $this->config->setPath(dirname(__DIR__))->loadConfig($cfg['config']);
        }

        if ($this->config->setConfig($cfg) === false) {
            throw new Exception('no parameters to run DocLister');
        }

        $this->loadLang(array('core', 'json'));
        $this->setDebug($this->getCFGDef('debug', 0));

        if ($this->checkDL()) {
            $cfg = array();
            $this->config->setConfig($cfg);
            $this->config->setConfig(array(
               'cacheKey' => 'recent'
            ));
            $this->alias = 's';
            $this->table = $this->getTable('comments_stat', $this->alias);
            $this->idField = $this->getCFGDef('idField', 'id');
            $this->parentField = $this->getCFGDef('parentField', 'parent');
            $this->extCache = $this->getExtender('cache', true);

            $this->extCache->init($this, array(
                'cache'         => $this->getCFGDef('cache', 1),
                'cacheKey'      => $this->getCFGDef('cacheKey'),
                'cacheLifetime' => $this->getCFGDef('cacheLifetime', 0),
                'cacheStrategy' => $this->getCFGDef('cacheStrategy')
            ));
        }
        $this->setLocate();

        if ($this->getCFGDef("customLang")) {
            $this->getCustomLang();
        }
        $this->loadExtender($this->getCFGDef("extender", ""));
        $DLTemplate = DLTemplate::getInstance($modx);
        if ($path = $this->getCFGDef('templatePath')) {
            $DLTemplate->setTemplatePath($path);
        }
        if ($ext = $this->getCFGDef('templateExtension')) {
            $DLTemplate->setTemplateExtension($ext);
        }
        $this->DLTemplate = $DLTemplate->setTemplateData(array('DocLister' => $this));
    }

    /**
     * Проверка параметров и загрузка необходимых экстендеров
     * return boolean статус загрузки
     */
    public function checkDL ()
    {
        $this->debug->debug('Check DocLister parameters', 'checkDL', 2);
        $flag = true;
        $extenders = $this->getCFGDef('extender', '');
        $extenders = explode(",", $extenders);
        $tmp = $this->getCFGDef('summary', '') != '' || in_array('summary', $extenders);
        if ($tmp && !$this->_loadExtender('summary')) {
            //OR summary in extender's parameter
            throw new Exception('Error load summary extender');
        }

        if ($this->getCFGDef('prepare', '') != '' || $this->getCFGDef('prepareWrap') != '') {
            $this->_loadExtender('prepare');
        }

        $this->config->setConfig(array('extender' => implode(",", $extenders)));
        $this->debug->debugEnd("checkDL");

        return $flag;
    }


    /**
     * @abstract
     */
    public function getDocs ($tvlist = '')
    {
        $out = $this->extCache->load('comments_data');
        if ($out === false) {
            $out = array();
            $from = "{$this->table} LEFT JOIN {$this->getTable('comments', 'c')} ON `s`.`last_comment` = `c`.`id` LEFT JOIN {$this->getTable('comments_guests' ,'g')} ON `g`.`id`=`c`.`id`  LEFT JOIN {$this->getTable('site_content', 'sc')} ON `c`.`thread` = `sc`.`id` AND `c`.`context` = 'site_content'";
            $limit = $this->LimitSQL();
            $fields = $this->getCFGDef('selectFields', '`s`.`comments_count`,`c`.*,`g`.`name`,`sc`.`pagetitle`');
            $rs = $this->dbQuery("SELECT {$fields} FROM {$from} WHERE `s`.`comments_count` > 0 AND `s`.`last_comment` > 0 AND `c`.`deleted` = 0 AND `c`.`published` = 1 AND `sc`.`deleted` =0 AND `sc`.`published` = 1 ORDER BY `c`.`id` DESC {$limit}");
            $this->loadExtender('user');
            /**
             * @var $extUser user_DL_Extender
             */
            if ($extUser = $this->getExtender('user')) {
                $extUser->init($this, array('fields' => $this->getCFGDef("userFields", "")));
            }

            /**
             * @var $extSummary summary_DL_Extender
             */
            $extSummary = $this->getExtender('summary');
            while ($row = $this->modx->db->getRow($rs)) {
                if ($extUser) {
                    $row = $extUser->setUserData($row);
                }
                $row['summary'] = $extSummary ? $this->getSummary(
                    $row,
                    $extSummary
                ) : '';
                $out[$row['id']] = $row;
            }
            $this->extCache->save($out, 'comments_data');
        }
        $this->_docs = $out;

        return $this->_docs;
    }

    /**
     * @param string $tpl
     * @return string
     */
    public function _render ($tpl = '')
    {
        $out = '';
        if ($tpl == '') {
            $tpl = $this->getCFGDef('tpl', '');
        }
        if ($tpl != '') {
            $out = $this->parseChunk($tpl, $this->_docs);
        }

        return $this->toPlaceholders($out);
    }

    /**
     * @absctract
     */
    public function getChildrenCount ()
    {
        if (empty($this->_docs)) {
            $this->getDocs();
        }

        return count($this->_docs);
    }

    /**
     * Выборка документов которые являются дочерними относительно $id документа и в тоже время
     * являются родителями для каких-нибудь других документов
     *
     * @param string|array $id значение PrimaryKey родителя
     * @return array массив документов
     */
    public function getChildrenFolder ($id)
    {
        return array();
    }


}
