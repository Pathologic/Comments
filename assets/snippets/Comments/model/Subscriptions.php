<?php namespace Comments;

use DocumentParser;

/**
 * Class Subscriptions
 * @package Comments
 * @property string $table
 * @property DocumentParser $modx
 * @property array $status
 */
class Subscriptions
{
    protected $table = 'comments_subscriptions';
    protected $modx;
    protected $status = [];
    protected static $instance;

    /**
     * @param DocumentParser $modx
     * @return Subscriptions
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
    public function __wakeup ()
    {
    }

    /**
     * @param $thread
     * @param string $context
     * @return bool
     */
    public function hasSubscription ($thread, $context = 'site_content')
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $uid = (int)$this->modx->getLoginUserID('web');
        $out = false;
        if ($uid && $context && $thread) {
            $key = $context . '_' . $thread;
            if (isset($this->status[$key])) {
                $out = $this->status[$key];
            } else {
                unset($this->status[$key]);
                $q = $this->modx->db->query("SELECT * FROM {$this->modx->getFullTableName($this->table)} WHERE `context` = '{$context}' AND `thread` = {$thread} AND `uid`={$uid}");
                if ($this->modx->db->getRecordCount($q)) {
                    $out = $this->status[$key] = true;
                }
            }
        }

        return $out;
    }

    /**
     * @param $thread
     * @param $context
     * @param int $uid
     * @return bool
     */
    public function subscribe ($thread, $context, $uid = 0)
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $uid = $uid ? (int)$uid : (int)$this->modx->getLoginUserID('web');
        $out = false;
        if ($uid && $thread && $context) {
            $this->modx->db->query("INSERT IGNORE INTO {$this->modx->getFullTableName($this->table)} (`context`, `thread`, `uid`) VALUES ('{$context}', {$thread}, {$uid})");
            $out = true;
        }

        return $out;
    }

    /**
     * @param $thread
     * @param $context
     * @param int $uid
     * @return bool
     */
    public function unsubscribe ($thread, $context, $uid = 0)
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $uid = $uid ? (int)$uid : (int)$this->modx->getLoginUserID('web');
        $out = false;
        if ($uid && $thread && $context) {
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName($this->table)} WHERE `context` = '{$context}' AND `thread` = {$thread} AND `uid`={$uid}");
            $out = true;
        }

        return $out;
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
                `uid` int(11) NOT NULL,
                UNIQUE KEY `subscription` (`context`, `thread`, `uid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        return $this;
    }
}
