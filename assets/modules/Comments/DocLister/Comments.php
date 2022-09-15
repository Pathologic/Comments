<?php namespace Comments\Module;

use DocumentParser;
use onetableDocLister;
use sqlHelper;

/**
 * Class Comments
 */
class CommentsDocLister extends onetableDocLister
{
    protected $table = 'comments';

    /**
     * Конструктор контроллеров DocLister
     *
     * @param DocumentParser $modx объект DocumentParser - основной класс MODX
     * @param mixed $cfg массив параметров сниппета
     * @param int $startTime время запуска сниппета
     * @throws \Exception
     */
    public function __construct ($modx, $cfg = array(), $startTime = null)
    {
        $cfg['selectFields'] = isset($cfg['selectFields']) ? $cfg['selectFields'] : 'c.id,c.thread,c.context,c.content as comment,c.createdby,c.published,c.deleted,c.ip,c.createdon,c.updatedon,sc.pagetitle as title,g.name';
        parent::__construct($modx, $cfg, $startTime);
        $this->setFiltersJoin("LEFT JOIN {$this->getTable('site_content', 'sc')} ON `c`.`thread` = `sc`.`id` AND `c`.`context` = 'site_content'");
        $this->setFiltersJoin("LEFT JOIN {$this->getTable('comments_guests', 'g')} ON `c`.`id` = `g`.`id` AND `c`.`createdby` = 0");
    }

    /**
     * @abstract
     */
    public function getDocs ($tvlist = '')
    {
        $this->_docs = $this->getDocList();
        $this->addUsers($this->_docs);
        if ($this->getCFGDef('mode') == 'single') {
            $out = count($this->_docs) ? array_values($this->_docs)[0] : false;
        } else {
            $out = [
                'rows'  => array_values($this->_docs),
                'total' => $this->getChildrenCount()
            ];
        }

        return $out;
    }

    /**
     * @param $data
     */
    protected function addUsers(&$data) {
        $users = [];
        foreach ($data as $item) {
            if ($item['createdby']) $users[] = $item['createdby'];
        }
        if ($users) {
            $ids = implode(',', $users);
            $users_table = $this->getTable(class_exists('EvolutionCMS\Core') ? 'users' : 'web_users');
            $q = $this->dbQuery("SELECT `id`,`username` FROM {$users_table} WHERE `id` IN ({$ids})");
            $users = [];
            while ($row = $this->modx->db->getRow($q)) {
                $users[$row['id']] = $row['username'];
            }
            foreach ($data as &$item) {
                if ($item['createdby'] && isset($users[$item['createdby']])) {
                    $item['username'] = $users[$item['createdby']];
                }
            }
            unset($item);
        }
    }

    /**
     * @return array
     */
    protected function getDocList ()
    {
        $out = array();
        $from = $this->table . " " . $this->_filters['join'];
        $where = $this->getCFGDef('addWhereList', '');

        //====== block added by Dreamer to enable filters ======
        $where = ($where ? $where . ' AND ' : '') . $this->_filters['where'];
        $where = sqlHelper::trimLogicalOp($where);
        //------- end of block -------


        if ($where != '') {
            $where = array($where);
        } else {
            $where = array();
        }
        if (!empty($where)) {
            $where = "WHERE " . implode(" AND ", $where);
        } else {
            $where = '';
        }

        $limit = $this->LimitSQL($this->getCFGDef('queryLimit', 0));
        $fields = $this->getCFGDef('selectFields', '*');
        $sort = $this->SortOrderSQL($this->getPK());
        $rs = $this->dbQuery("SELECT {$fields} FROM {$from} {$where} {$sort} {$limit}");
        $pk = $this->getPK(false);
        $extSummary = $this->getExtender('summary');
        while ($item = $this->modx->db->getRow($rs)) {
            $item['comment'] = rtrim($this->getSummary($item, $extSummary, '', 'comment'), '.');
            $out[$item[$pk]] = $item;
        }

        return $out;
    }

    /**
     * @absctract
     */
    public function getChildrenCount ()
    {
        $out = 0;
        $from = $this->table . " " . $this->_filters['join'];
        $where = $this->getCFGDef('addWhereList', '');

        //====== block added by Dreamer ======
        $where = ($where ? $where . ' AND ' : '') . $this->_filters['where'];
        $where = sqlHelper::trimLogicalOp($where);
        //------- end of block -------

        if ($where != '') {
            $where = array($where);
        } else {
            $where = array();
        }
        if (!empty($where)) {
            $where = "WHERE " . implode(" AND ", $where);
        } else {
            $where = '';
        }

        $group = $this->getGroupSQL($this->getCFGDef('groupBy', $this->getPK()));
        $maxDocs = $this->getCFGDef('maxDocs', 0);
        $limit = $maxDocs > 0 ? $this->LimitSQL($this->getCFGDef('maxDocs', 0)) : '';
        $rs = $this->dbQuery("SELECT count(*) FROM (SELECT count(*) FROM {$from} {$where} {$group} {$limit}) as `tmp`");
        $out = $this->modx->db->getValue($rs);

        return $out;
    }
}
