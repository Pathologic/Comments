<?php namespace Comments;

use APIhelpers;
use Comments\Traits\Messages;
use Doctrine\Common\Cache\Cache;
use DocumentParser;

/**
 * Class Rating
 * @package Comments
 * @property DocumentParser $modx
 * @property Comments $comment
 */
class Rating {
    use Messages;
    protected $modx;
    protected $comment;
    protected $table = 'comments_rating';
    protected $logTable = 'comments_rating_log';

    protected static $instance;

    /**
     * @param DocumentParser $modx
     * @return Rating
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
        $this->comment = new Comments($modx);
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
     * @param $commentId
     * @param bool $fire_events
     * @param bool $clearCache
     * @return bool
     */
    public function like ($commentId, $fire_events = true, $clearCache = false)
    {
        $commentId = (int)$commentId;
        $out = true;
        $uid = (int)$this->modx->getLoginUserID('web');
        if ($uid && $this->getComment($commentId) && (int)$this->comment->get('createdby') !== $uid && !$this->isVoted($commentId)) {
            $result = $this->modx->invokeEvent('OnBeforeCommentVote', [
                'vote'    => 'dislike',
                'comment' => $this->comment
            ], $fire_events);
            if (!empty($result)) {
                $out = false;
                $this->addMessages($result);
            } else {
                $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`comment`, `like`) VALUES ({$commentId}, 1) ON DUPLICATE KEY UPDATE `like` = `like` + 1");
                $this->calculateRating($commentId);
                $out = true;
            }
            if ($out) {
                $result = $this->modx->invokeEvent('OnCommentVote', [
                    'vote'    => 'dislike',
                    'comment' => $this->comment
                ], $fire_events);
                if (!empty($result)) {
                    $this->addMessages($result);
                }
                if ($clearCache) {
                    $this->dropCache($this->comment->get('thread'), $this->comment->get('context'));
                }
                $this->saveVote($commentId, 'like');
            }
        }

        return $out;
    }

    /**
     * @param $commentId
     * @param bool $fire_events
     * @param bool $clearCache
     * @return bool
     */
    public function dislike ($commentId, $fire_events = true, $clearCache = false)
    {
        $commentId = (int)$commentId;
        $out = true;
        $uid = (int)$this->modx->getLoginUserID('web');
        if ($uid && $this->getComment($commentId) && (int)$this->comment->get('createdby') !== $uid && !$this->isVoted($commentId)) {
            $result = $this->modx->invokeEvent('OnBeforeCommentVote', [
                'vote'    => 'dislike',
                'comment' => $this->comment
            ], $fire_events);
            if (!empty($result)) {
                $out = false;
                $this->addMessages($result);
            } else {
                $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`comment`, `dislike`) VALUES ({$commentId}, 1) ON DUPLICATE KEY UPDATE `dislike` = `dislike` + 1");
                $this->calculateRating($commentId);
                $out = true;
            }
            if ($out) {
                $result = $this->modx->invokeEvent('OnCommentVote', [
                    'vote'    => 'dislike',
                    'comment' => $this->comment
                ], $fire_events);
                if (!empty($result)) {
                    $this->addMessages($result);
                }
                if ($clearCache) {
                    $this->dropCache($this->comment->get('thread'), $this->comment->get('context'));
                }
                $this->saveVote($commentId, 'dislike');
            }
        }

        return $out;

    }

    /**
     * @param $commentId
     */
    public function calculateRating($commentId) {
        $commentId = (int)$commentId;
        $this->modx->db->query("UPDATE {$this->modx->getFullTableName($this->table)} SET `rating`=((`like` + 1.9208) / (`like`+`dislike`+1) -1.96 * SQRT((`like` * (`dislike` + 1)) / `like` + 0.9604) / (`like`+`dislike`+1)) / (1 + 3.8416 / (`like`+`dislike`+1)) WHERE `comment`={$commentId}");
    }

    /**
     * @param $commentId
     * @return bool
     */
    public function isVoted($commentId) {
        $out = false;
        $commentId = (int)$commentId;
        $uid = (int)$this->modx->getLoginUserID('web');
        if ($commentId && $uid) {
            $q = $this->modx->db->query("SELECT `comment` FROM {$this->modx->getFullTableName($this->logTable)} WHERE `comment` = {$commentId} AND `uid`={$uid}");
            $out = $this->modx->db->getValue($q) !== false;
        }

        return $out;
    }

    /**
     * @param $commentId
     * @param $vote
     */
    public function saveVote($commentId, $vote) {
        $commentId = (int)$commentId;
        $uid = (int)$this->modx->getLoginUserID('web');
        if ($commentId && $uid) {
            $vote = $vote == 'like' ? 'like' : 'dislike';
            $ip = APIhelpers::getUserIP();
            $createdon = date('Y-m-d H:i:s', time() + $this->modx->getConfig('server_offset_time'));
            $this->modx->db->query("INSERT IGNORE INTO {$this->modx->getFullTableName($this->logTable)} (`comment`,`uid`, `vote`, `ip`, `createdon`) VALUES ({$commentId}, {$uid}, '{$vote}', '{$ip}', '{$createdon}')");
        }
    }

    /**
     * @param $commentId
     * @return bool
     */
    protected function getComment($commentId) {
        return (int)$this->comment->edit($commentId)->getID() === $commentId && $this->comment->get('published') && !$this->comment->get('deleted');
    }

    /**
     * @param $commentId
     * @param $like
     * @param $dislike
     */
    public function set($commentId, $like, $dislike) {
        $commentId = (int)$commentId;
        if ($commentId && $like >= 0 && $dislike >= 0) {
            $total = $like + $dislike;
            $rating = (($like + 1.9208) / $total -
                    1.96 * sqrt(($like * $dislike) / $total + 0.9604) /
                    $total) / (1 + 3.8416 / $total);
            if ($total > 0) {
                $this->modx->db->query("INSERT INTO {$this->modx->getFullTableName($this->table)} (`comment`, `like`, `dislike`, `rating`) VALUES ({$commentId}, {$like}, {$dislike}, {$rating}) ON DUPLICATE KEY UPDATE `like`={$like}, `dislike` = {$dislike}, `rating`=(({$like} + 1.9208) / {$total} -
                    1.96 * SQRT(({$like} * {$dislike}) / {$total} + 0.9604) /
                    {$total}) / (1 + 3.8416 / {$total})");
            }
        }
    }

    /**
     * @param $commentId
     * @return array
     */
    public function get($commentId) {
        $commentId = (int)$commentId;
        $q = $this->modx->db->query("SELECT `like`, `dislike`, `rating` FROM {$this->modx->getFullTableName($this->table)} WHERE `comment` = {$commentId}");
        if (!$out = $this->modx->db->getRow($q)) {
            $out = [
                'like' => 0,
                'dislike' => 0,
                'rating' => 0
            ];
        }
        $out['count'] = $out['like'] - $out['dislike'];

        return $out;
    }

    /**
     * @param int $thread
     * @param string $context
     * @return Rating
     */
    public function dropCache ($thread = 0, $context = '')
    {
        if (isset($this->modx->cache) && ($this->modx->cache instanceof Cache)) {
            if ($thread && $context) {
                $key = $context . '_' . $thread . '_comments_rating';
                $this->modx->cache->delete($key);
            }
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
                `comment` INT(11) NOT NULL,
                `like` INT(11) NOT NULL,
                `dislike` INT(11) NOT NULL,
                `rating` FLOAT NOT NULL DEFAULT 0,
                PRIMARY KEY `comment` (`comment`),
                CONSTRAINT `comments_rating_ibfk_1`
                FOREIGN KEY (`comment`) 
                REFERENCES {$this->modx->getFullTableName('comments')} (`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->modx->db->query("
            CREATE TABLE IF NOT EXISTS {$this->modx->getFullTableName($this->logTable)} (
                `comment` INT(11) NOT NULL,
                `uid` INT(11) NOT NULL,
                `vote` varchar(7) NOT NULL,
                `ip` varchar(16) NOT NULL DEFAULT '0.0.0.0',
                `createdon` timestamp,
                CONSTRAINT `comments_rating_log_ibfk_1`
                FOREIGN KEY (`comment`) 
                REFERENCES {$this->modx->getFullTableName('comments')} (`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE KEY `vote` (`comment`, `uid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->modx->db->query("
            INSERT IGNORE INTO {$this->modx->getFullTableName('system_eventnames')} (`name`, `groupname`) VALUES 
            ('OnBeforeCommentVote', 'Comments Events'),
            ('OnCommentVote', 'Comments Events')
        ");

        return $this;
    }
}
