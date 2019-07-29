<?php namespace Comments;

use APIhelpers;

/**
 * Class Plugin
 * @package Comments
 */
class Plugin
{
    protected $modx = null;
    protected $data = null;
    protected $jsList = 'assets/plugins/tmuploader/js/scripts.json';

    /**
     * Plugin constructor.
     * @param \DocumentParser $modx
     */
    public function __construct (\DocumentParser $modx)
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
            foreach($ids as $thread) {
                $this->data->dropCache($thread, 'site_content');
            }
            $this->data->dropCache();
        }
    }

    public function OnWebDeleteUser () {
        $userid = (int)$userid;
        if ($userid) {
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('comments_lastview')} WHERE  `uid` IN ({$userid})");
        }
    }
}
