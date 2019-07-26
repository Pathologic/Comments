<?php namespace Comments;

use DocumentParser;

/**
 * Class ThreadsMeta
 * @package Comments
 */
class Stat
{
    protected $table = 'comments_stat';
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
     * @return array|bool|mixed|object|\stdClass|void
     */
    public function getStat ($thread, $context = 'site_content')
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $q = $this->modx->db->query("SELECT `last_comment`, `comments_count` FROM {$this->modx->getFullTableName($this->table)} WHERE `context` = '{$context}' AND `thread` = {$thread}");
        if ($row = $this->modx->db->getRow($q)) {
            $out = $row;
        } else {
            $out = array(
                'last_comment'   => 0,
                'comments_count' => 0
            );
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
    public function setLastComment ($lastComment, $thread, $context = 'site_content')
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        $lastComment = (int)$lastComment;
        if (!empty($context) && $thread > 0 && $lastComment > 0) {
            $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`context`, `thread`, `last_comment`, `comments_count`) VALUES ('{$context}', {$thread}, {$lastComment}, 1) ON DUPLICATE KEY UPDATE `last_comment`={$lastComment}, `comments_count` = `comments_count` + 1");
        }

        return $this;
    }

    /**
     * Обновление последнего опубликованного комментария
     * @param $context
     * @param $thread
     * @param int $commentId
     * @return $this
     */
    public function updateLastComment ($thread, $context = 'site_content')
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        if (!empty($context) && $thread > 0) {
            $q = $this->modx->db->query("SELECT MAX(`id`) FROM {$this->modx->getFullTableName('comments')} WHERE `thread`={$thread} AND `context`='{$context}' AND `published`=1 AND `deleted`=0");
            $lastComment = (int)$this->modx->db->getValue($q);
            if ($lastComment > 0) {
                $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`context`, `thread`, `last_comment`) VALUES ('{$context}', {$thread}, {$lastComment}) ON DUPLICATE KEY UPDATE `last_comment`={$lastComment}");
            }
        }

        return $this;
    }

    /**
     * Обновление количества опубликованных комментариев
     * @param $context
     * @param $thread
     * @return $this
     */
    public function updateCommentsCount ($thread, $context = 'site_content')
    {
        $context = is_scalar($context) ? $this->modx->db->escape($context) : '';
        $thread = (int)$thread;
        if (!empty($context) && $thread > 0) {
            $q = $this->modx->db->query("SELECT COUNT(`id`) FROM {$this->modx->getFullTableName('comments')} WHERE `thread`={$thread} AND `context`='{$context}' AND `published`=1 AND `deleted`=0");
            $count = (int)$this->modx->db->getValue($q);
            if ($count !== false) {
                $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`context`, `thread`, `comments_count`) VALUES ('{$context}', {$thread}, {$count}) ON DUPLICATE KEY UPDATE `comments_count`={$count}");
            }
        }

        return $this;
    }

    /**
     * Полный пересчет
     * @return $this
     */
    public function updateAll ()
    {
        $this->modx->db->query("TRUNCATE TABLE {$this->modx->getFullTableName($this->table)}");
        $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} SELECT `context`, `thread`, MAX(`id`) as `last_comment`, COUNT(`id`) as `comments_count` FROM {$this->modx->getFullTableName('comments')} WHERE `published`=1 AND `deleted`=0 GROUP BY `thread`, `context` ON DUPLICATE KEY UPDATE `last_comment`=VALUES(`last_comment`), `comments_count`=VALUES(`comments_count`)");

        return $this;
    }

    /**
     * @return $this
     */
    public function createTable ()
    {
        $this->modx->db->query("
            CREATE TABLE IF NOT EXISTS {$this->modx->getFullTableName('comments_stat')} (
                `context` varchar(255) NOT NULL,
                `thread` int(11) NOT NULL,
                `last_comment` int(11) NOT NULL,
                `comments_count` int(11) NOT NULL,
                UNIQUE KEY `thread` (`thread`, `context`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        return $this;
    }
}
