<?php namespace Comments;

use DocumentParser;

/**
 * Class LastView
 * @package Comments
 */
class LastView
{
    protected $table = 'comments_lastview';
    private $modx = null;
    protected static $instance;

    /**
     * @param DocumentParser $modx
     * @return Stat
     */
    public static function getInstance (DocumentParser $modx)
    {
        if (null === self::$instance) {
            self::$instance = new self($modx);
        }

        return self::$instance;
    }

    /**
     * ThreadsMeta constructor.
     * @param DocumentParser $modx
     */
    private function __construct (DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    /**
     *
     */
    private function __clone ()
    {
    }

    /**
     *
     */
    private function __wakeup ()
    {
    }

    /**
     * @param $thread
     * @param string $context
     * @return int
     */
    public function getLastView ($thread, $context = 'site_content')
    {
        $out = false;
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $uid = (int)$this->modx->getLoginUserID('web');
        if ($uid) {
            $q = $this->modx->db->query("SELECT `last_comment` FROM {$this->modx->getFullTableName($this->table)} WHERE `context` = '{$context}' AND `thread` = {$thread} AND `uid`={$uid}");
            $out = (int)$this->modx->db->getValue($q);
        }

        return $out;
    }

    /**
     * Задание последнего опубликованного комментария и увеличение количества на 1
     * @param $lastComment
     * @param $thread
     * @param string $context
     * @return $this
     */
    public function setLastView ($commentId, $thread, $context = 'site_content')
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $commentId = (int)$commentId;
        $uid = (int)$this->modx->getLoginUserID('web');
        if (!empty($context) && $thread > 0 && $commentId > 0 && $uid) {
            $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`context`, `thread`, `last_comment`, `uid`) VALUES ('{$context}', {$thread}, {$commentId}, {$uid}) ON DUPLICATE KEY UPDATE `last_comment`={$commentId}");
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function createTable ()
    {
        $this->modx->db->query("
            CREATE TABLE IF NOT EXISTS {$this->modx->getFullTableName($this->table)} (
                `context` varchar(255) NOT NULL,
                `thread` int(11) NOT NULL,
                `last_comment` int(11) NOT NULL,
                `uid` int(11) NOT NULL,
                UNIQUE KEY `thread_uid` (`thread`, `context`, `uid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        return $this;
    }
}
