<?php

use Comments\LastView;
use Helpers\Config;
use Helpers\FS;
use Comments\Traits\DocLister as CommentsTrait;
use Helpers\Lexicon;

/**
 * Class TreeView
 */
class TreeViewDocLister extends DocLister
{
    use CommentsTrait;

    protected $table = 'comments';
    protected $alias = 'c';

    protected $idField = 'id';
    protected $context = 'site_content';
    public $mode = 'comments';
    public $lastComment = 0;
    public $commentsCount = 0;
    protected $order = [];
    protected $hidden = [];
    protected $relations = [];
    protected $lexicon;

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

        $this->FS = FS::getInstance();
        $this->config = new Config($cfg);

        if (isset($cfg['config'])) {
            $this->config->setPath(dirname(__DIR__))->loadConfig($cfg['config']);
        }

        if ($this->config->setConfig($cfg) === false) {
            throw new Exception('no parameters to run DocLister');
        }
        $this->mode = $this->getCFGDef('mode', 'comments');
        $this->config->setConfig(array(
            'cacheKey' => $this->getCFGDef('context', 'site_content') . '_' . $this->getCFGDef('thread')
        ));
        if ($this->mode == 'recent') {
            $this->config->setConfig(array(
                'cache' => 0
            ));
        }
        $this->setDebug($this->getCFGDef('debug', 0));
        if ($this->checkDL()) {
            $this->alias = empty($this->alias) ? $this->getCFGDef(
                'tableAlias',
                'c'
            ) : $this->alias;
            $this->table = $this->getTable(empty($this->table) ? $this->getCFGDef(
                'table',
                'comments'
            ) : $this->table, $this->alias);

            $this->extCache = $this->getExtender('cache', true);
            $this->extCache->init($this, array(
                'cache'         => $this->getCFGDef('cache', 1),
                'cacheKey'      => $this->getCFGDef('cacheKey'),
                'cacheLifetime' => $this->getCFGDef('cacheLifetime', 0),
                'cacheStrategy' => $this->getCFGDef('cacheStrategy')
            ));
            $IDs = $this->getCFGDef('thread', $this->getCurrentMODXPageID());
            $this->setIDs($IDs);
            $this->setContext($this->getCFGDef('context', 'site_content'));
        }

        $this->setLocate();

        $this->lexicon = new Lexicon($modx, array(
            'langDir' => 'assets/snippets/Comments/lang/',
            'lang'    => $this->getCFGDef('lang', $this->modx->getConfig('lang_code')),
            'handler' => $this->getCFGDef('lexiconHandler')
        ));
        if ($this->getCFGDef("customLang")) {
            $this->getCustomLang();
        }
        $this->lexicon->fromFile('treeview');
        $this->loadExtender($this->getCFGDef("extender", ""));
        $DLTemplate = DLTemplate::getInstance($modx);
        if ($path = $this->getCFGDef('templatePath')) {
            $DLTemplate->setTemplatePath($path);
        }
        if ($ext = $this->getCFGDef('templateExtension')) {
            $DLTemplate->setTemplateExtension($ext);
        }
        $this->DLTemplate = $DLTemplate->setTemplateData(array('DocLister' => $this));
        $this->setFiltersJoin("LEFT JOIN {$this->getTable('comments_guests')} `g` ON `c`.`id` = `g`.`id`");
        $this->setFiltersJoin("JOIN {$this->getTable('comments_tree')} `t` ON `c`.`id` = `t`.`idDescendant`");
        $this->initModeration();
        if ($this->mode == 'comments' && $this->getCFGDef('rtss', 1)) {
            $this->saveSettings();
        }
    }

    /**
     * @param $message
     * @return string
     */
    public function translate($message) {
         return $this->lexicon->get($message);
    }

    protected function saveSettings ()
    {
        $rtss = RuntimeSharedSettings::getInstance($this->getMODX());
        $rtss->save(
            $this->getCFGDef('rtssElement', 'TreeViewComments'),
            $this->getContext(),
            $this->config->getConfig()
        );
    }

    /**
     * Проверка параметров и загрузка необходимых экстендеров
     * return boolean статус загрузки
     */
    public function checkDL ()
    {
        $this->debug->debug('Check DocLister parameters', 'checkDL', 2);
        $flag = true;
        $this->debug->debugEnd("checkDL");

        return $flag;
    }

    /**
     * @return array
     */
    public function getCommentsOrder ()
    {
        return $this->order;
    }

    /**
     * @abstract
     */
    public function getDocs ($tvlist = '')
    {
        $this->getDocList();
        $count = 0;
        $trackNew = $this->getCFGDef('trackNewComments', 1);
        $lastView = false;
        if ($trackNew) {
            $lastView = LastView::getInstance($this->modx)->getLastView($this->getCFGDef('thread'), $this->getContext());
        }
        $enableRating = (bool)$this->getCFGDef('rating', 1);
        $uid = $this->modx->getLoginUserID('web');
        if ($enableRating) {
            $this->getRating()->getRated();
        }
        foreach ($this->_docs as &$item) {
            $item['rateable'] = $enableRating;
            if ($enableRating) {
                if (isset($this->rating[$item['id']])) {
                    $item['rating'] = $this->rating[$item['id']];
                } else {
                    $item['rating'] = [
                        'like' => 0,
                        'dislike' => 0,
                        'count' => 0,
                        'rating' => 0
                    ];
                }
                if ($this->isRated($item['id']) || !$uid || $item['createdby'] == $uid) {
                    $item['classes'][] = $this->getCFGDef('ratedClass', 'rated');
                }
                if ($item['rating']['count'] > 0) {
                    $item['classes'][] = $this->getCFGDef('positiveClass', 'positive');
                } elseif ($item['rating']['count'] < 0) {
                    $item['classes'][] = $this->getCFGDef('negativeClass', 'negative');
                }
            }
            $editable = $this->isEditable($item);
            if ($editable) {
                $item['editable'] = (bool)$editable;
                $item['edit-ttl'] = $editable;
            }
            if ($item['idNearestAncestor']) {
                unset($this->_docs[$item['idNearestAncestor']]['editable']);
            }
            if ($item['idNearestAncestor'] > 0) {
                $docs[$item['idNearestAncestor']]['editable'] = false;
            }
            if (isset($item['editable'])) {
                $item['classes'][] = $this->getCFGDef('editableClass', 'editable');
            }
            if ($this->isModerator() || ($item['published'] && !$item['deleted'])) {
                $count++;
            }
            if($trackNew && $lastView !== false && $item['id'] > $lastView) {
                $item['new'] = true;
                $item['classes'][] = $this->getCFGDef('newClass', 'new');
            }

            $item['classes'][] = $this->getCFGDef('levelClass', 'level') . $item['level'];
        }
        unset($item);
        $this->commentsCount = $count;
        $keys = array_keys($this->_docs);
        $this->lastComment = (int)end($keys);
        if ($trackNew) {
            LastView::getInstance($this->modx)->setLastView($this->lastComment, $this->getCFGDef('thread'), $this->getContext());
        }

        return $this->_docs;
    }

    /**
     * Подготовка результатов к отображению в соответствии с настройками
     *
     * @param string $tpl шаблон
     * @return string
     */
    public function render ($tpl = '')
    {
        switch ($this->mode) {
            case 'recent':
                $this->outData = $this->_renderRecent($tpl);
                break;
            case 'comments':
                $this->outData = $this->_render($tpl);
                break;
            default:
                break;
        }

        return $this->outData;
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
        if (empty($tpl)) {
            return $out;
        }
        foreach ($this->order as $id) {
            if (!isset($this->_docs[$id])) {
                continue;
            }
            $out .= $this->parseChunk($tpl, $this->_docs[$id]);
        }
        $out = $this->renderWrap($out);

        return $this->toPlaceholders($out);
    }

    /**
     * @param string $tpl
     * @return false|string
     */
    public function _renderRecent ($tpl = '')
    {
        $out = array();
        if ($tpl == '') {
            $tpl = $this->getCFGDef('tpl', '');
        }
        if (empty($tpl)) {
            return '';
        }

        foreach ($this->relations as $parent => $children) {
            foreach ($children as $id) {
                if (isset($this->_docs[$id])) {
                    if (isset($out[$parent])) {
                        $out[$parent] .= $this->parseChunk($tpl, $this->_docs[$id]);
                    } else {
                        $out[$parent] = $this->parseChunk($tpl, $this->_docs[$id]);
                    }
                }
            }
        }

        return jsonHelper::toJSON(array(
                'comments'    => $out,
                'count'       => $this->commentsCount,
                'lastComment' => $this->lastComment
            )
        );
    }

    /**
     * Помещение html кода в какой-то блок обертку
     *
     * @param string $data html код который нужно обернуть в ownerTPL
     * @return string результатирующий html код
     */
    public function renderWrap ($data)
    {
        $wrapTpl = $this->getCFGDef('wrapTpl', '');
        $plh = array('wrap' => $data, 'count' => $this->commentsCount);
        $out = $this->parseChunk($wrapTpl, $plh);
        $this->toPlaceholders($this->lastComment, true, 'lastComment');

        return $out;
    }

    /**
     * @param array $data
     * @param mixed $fields
     * @param array $array
     * @return string
     */
    public function getJSON ($data, $fields, $array = array())
    {
        $out = array();
        foreach ($this->order as $id) {
            if (!isset($this->_docs[$id])) {
                continue;
            }
            $out[] = $this->_docs[$id];
        }

        return jsonHelper::toJSON(array(
                'comments'    => $out,
                'count'       => $this->commentsCount,
                'lastComment' => $this->lastComment
            )
        );
    }

    /**
     * @return $this
     */
    protected function getDocList ()
    {
        $out = $this->extCache->load('comments_data' . ($this->isModerator() ? '_moderation' : ''));
        if ($out === false) {
            $thread = $this->sanitarIn($this->IDs);
            $hideUnpublished = $this->getCFGDef('hideUnpublished', 1) && !$this->isModerator();
            $hideDeleted = $this->getCFGDef('hideDeleted', 0) && !$this->isModerator();
            $from = $this->table . " " . $this->_filters['join'];
            $where = "WHERE `t`.`idDescendant` = `t`.`idAncestor` AND `c`.`thread` = {$thread} AND `c`.`context`= '{$this->getContext()}'";
            if ($addWhereList = $this->getCFGDef('addWhereList')) {
                $where .= ' AND ' . $addWhereList;
            }
            $sort = "ORDER BY `c`.`id` ASC";
            $fields = $this->getCFGDef('selectFields', '
                `c`.*,
                `g`.`name`,
                `g`.`email`,
                `t`.`idAncestor`,
                `t`.`idDescendant`,
                `t`.`idNearestAncestor`,
                `t`.`level`'
            );
            $rs = $this->dbQuery("SELECT {$fields} FROM {$from} {$where} {$sort}");
            $pk = $this->getPK(false);
            while ($item = $this->modx->db->getRow($rs)) {
                $this->relations[$item['idNearestAncestor']][] = $item['id'];
                if (($hideUnpublished && !$item['published']) || ($hideDeleted && $item['deleted'])) {
                    $this->hidden[$item['id']] = $item['idNearestAncestor'];
                    continue;
                }
                $item['classes'] = $this->getClasses($item);
                $item['attachments'] = [];
                $this->_docs[$item[$pk]] = $item;
            }
            $this->loadExtender('user');
            if ($extUser = $this->getExtender('user')) {
                $extUser->init($this,
                    array('fields' => $this->getCFGDef('userFields', 'createdby,editedby,deletedby')));
                foreach ($this->_docs as &$item) {
                    $item = $extUser->setUserData($item);
                }
                unset($item);
            }
            $this->fixGaps();
            if ($extendedFields = $this->getExtendedFields()) {
                foreach ($extendedFields as $key => $value) {
                    $this->_docs[$key] += $value;
                }
            }
            $ids = array_keys($this->_docs);
            if ($ids) {
                $ids = implode(',', $ids);
                $q = $this->dbQuery("SELECT `f`.*, `a`.`comment` FROM {$this->getTable('comments_files')} `f` LEFT JOIN {$this->getTable('comments_attachments')} `a` ON `f`.`id` = `a`.`attachment` AND `a`.`comment` IN ({$ids}) WHERE NOT ISNULL(`a`.`comment`)");
                while ($row = $this->modx->db->getRow($q)) {
                    $this->_docs[$row['comment']]['attachments'][$row['id']] = $row;
                }
            }
            $this->order = $this->buildFlatTree();
            $this->extCache->save($this->_docs, 'comments_data' . ($this->isModerator() ? '_moderation' : ''));
            $this->extCache->save($this->relations, 'comments_relations');
            $this->extCache->save($this->order, 'comments_order');
        } else {
            $this->_docs = $out;
            $this->relations = $this->extCache->load('comments_relations');
            $this->order = $this->extCache->load('comments_order');
        }

        return $this;
    }

    /**
     * Коррекция для вывода ответов на скрытые комментарии
     */
    public function fixGaps ()
    {
        foreach ($this->hidden as $id => $idNearestAncestor) {
            if (!isset($this->relations[$id])) continue;
            foreach ($this->relations[$id] as $_id) {
                if (!isset($this->_docs[$_id])) continue;
                $this->_docs[$_id]['idNearestAncestor'] = $idNearestAncestor;
            }
            $children = $this->buildFlatTree($id);
            foreach ($children as $child) {
                if (isset($this->_docs[$child])) {
                    $this->_docs[$child]['level'] += -1;
                }
            }
        }
    }

    /**
     * @param $treeData
     * @param int $idAncestor
     * @return array
     */
    protected function buildFlatTree ($idAncestor = 0, &$out = array())
    {
        if (isset($this->relations[$idAncestor])) {
            foreach ($this->relations[$idAncestor] as $idAncestor) {
                $out[] = $idAncestor;
                $this->buildFlatTree($idAncestor, $out);
            }
        }

        return $out;
    }

    /**
     * @absctract
     */
    public function getChildrenCount ()
    {
        $thread = (int)$this->IDs;
        $from = $this->getTable($this->table);
        $where = array();
        $where[] = "`c`.`thread` = {$thread} AND `c`.`context`= '{$this->getContext()}'";
        if ($this->getCFGDef('hideUnpublished', 1)) {
            $where[] = '`c`.`published` = 1';
        }
        if ($this->getCFGDef('hideDeleted', 1)) {
            $where[] = '`c`.`deleted` = 0';
        }
        $where[] = $this->getCFGDef('addWhereList', '');
        $where = array_filter($where);
        $where = 'WHERE ' . implode(" AND ", $where);
        $rs = $this->dbQuery("SELECT COUNT(*) FROM {$from} {$where}");
        $out = (int)$this->modx->db->getValue($rs);

        return $out;
    }

    public function getChildrenFolder ($id)
    {
        // TODO: Implement getChildrenFolder() method.
    }

    /**
     * TODO:
     * @param $treeData
     * @param int $idAncestor
     * @return array|null
     */
    protected function buildHierarchyArrayTree ($idAncestor = 0)
    {
        $tree = array();
        if (is_int($idAncestor) && $idAncestor >= 0) {
            $treedata = array_keys($this->_docs);
            foreach ($treedata as $id) {
                if ((int)$this->_docs[$id]['idNearestAncestor'] === (int)$idAncestor) {
                    $tree[] = array(
                        'id'       => (int)$id,
                        'data'     => $this->_docs[$id],
                        'children' => $this->buildHierarchyArrayTree($id)
                    );
                }
            }
        }

        return $tree;
    }

}
