<?php namespace Comments;

use APIhelpers;
use DocumentParser;

/**
 * Class Plugin
 * @package Comments
 * @property DocumentParser $modx
 * @property Comments $data;
 */
class Plugin
{
    protected $modx;
    protected $data;

    /**
     * Plugin constructor.
     * @param DocumentParserAlias $modx
     */
    public function __construct (DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->data = new Comments($modx);
    }

    public function OnEmptyTrash ()
    {
        $ids = APIhelpers::cleanIDs($this->modx->event->params['ids']);
        $_ids = implode(',', $ids);
        if ($_ids) {
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments')} WHERE `context`='site_content' AND `thread` IN ({$_ids})");
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments_stat')} WHERE `context`='site_content' AND `thread` IN ({$_ids})");
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments_lastview')} WHERE `context`='site_content' AND `thread` IN ({$_ids})");
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments_subscriptions')} WHERE `context`='site_content' AND `thread` IN ({$_ids})");
        }
    }

    public function OnWebDeleteUser () {
        $userid = (int)$this->modx->event->params['userid'];
        if ($userid) {
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments_lastview')} WHERE  `uid` IN ({$userid})");
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments_subscriptions')} WHERE `uid` IN ({$userid})");
        }
    }

    public function OnSiteRefresh() {
        $ttl = (int)$this->modx->event->params['ttl'] ?? 24;
        $files = new Files($this->modx);
        $files->deleteLostFiles($ttl);
    }
}
