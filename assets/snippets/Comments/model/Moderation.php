<?php namespace Comments;

use DocumentParser;
use Helpers\Config;

/**
 * Class Moderation
 * @package Comments
 */
class Moderation
{
    protected $modx = null;
    protected $cfg = array();

    /**
     * Moderation constructor.
     * @param DocumentParser $modx
     */
    public function __construct (DocumentParser $modx, array $cfg = array())
    {
        $this->modx = $modx;
        $this->cfg = new Config($cfg);
    }

    /**
     * @param array $options
     * @return bool
     */
    public function isModerator ()
    {
        $uid = $this->modx->getLoginUserID('web');
        $hasPermission = $this->hasPermission('comments_publish') ||
            $this->hasPermission('comments_unpublish') ||
            $this->hasPermission('comments_delete') ||
            $this->hasPermission('comments_undelete') ||
            $this->hasPermission('comments_remove') ||
            $this->hasPermission('comments_edit');
        $moderatedByThreadCreator = $this->getCFGDef('moderatedByThreadCreator', 0);
        $threadCreator = $this->getCFGDef('threadCreator', 0);

        return $uid && ($hasPermission || ($moderatedByThreadCreator && $uid == $threadCreator));
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function hasPermission ($permission = '')
    {
        $uid = $this->modx->getLoginUserID('web');
        $moderatedByThreadCreator = $this->getCFGDef('moderatedByThreadCreator', 0);
        $threadCreator = $this->getCFGDef('threadCreator', 0);
        $out = $moderatedByThreadCreator && $uid == $threadCreator;
        if (!$out && !empty($_SESSION['usrPermissions']) && is_array($_SESSION['usrPermissions'])) {
            $out = in_array($permission, $_SESSION['usrPermissions']);
        }

        return $out;
    }

    /**
     * @param array $cfg
     */
    public function setConfig($cfg = array()) {
        $this->cfg->setConfig($cfg);
    }

    /**
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    protected function getCFGDef ($key, $default = null)
    {
        return $this->cfg->getCFGDef($key, $default);
    }
}
