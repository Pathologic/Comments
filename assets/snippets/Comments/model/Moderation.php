<?php namespace Comments;

use autoTable;
use DocumentParser;
use Helpers\Config;

/**
 * Class Moderation
 * @package Comments
 * @property DocumentParser $modx
 * @property autoTable $data
 * @property Config $cfg
 */
class Moderation
{
    protected $modx;
    protected $data;
    protected $cfg;

    /**
     * Moderation constructor.
     * @param DocumentParser $modx
     */
    public function __construct (DocumentParser $modx, array $cfg = [])
    {
        $this->modx = $modx;
        $this->cfg = new Config($cfg);
        if ($this->modx->getLoginUserID('web')) {
            $this->loadModel();
        }
    }

    /**
     * @param array $options
     * @return bool
     */
    public function isModerator ()
    {
        $hasPermission = $this->hasPermission('comments_publish') ||
            $this->hasPermission('comments_unpublish') ||
            $this->hasPermission('comments_delete') ||
            $this->hasPermission('comments_undelete') ||
            $this->hasPermission('comments_remove') ||
            $this->hasPermission('comments_edit');

        return $hasPermission || ($this->getCFGDef('moderatedByThreadCreator', 1) && $this->isThreadCreator());
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function hasPermission ($permission = '')
    {
        if (class_exists('EvolutionCMS\Core')) {
			$out = $this->modx->hasPermission($permission);
        } else {
            $out = !empty($_SESSION['usrPermissions']) && is_array($_SESSION['usrPermissions']) && in_array($permission, $_SESSION['usrPermissions']);
        }

        return $out;
    }

    /**
     * @param int $uid
     * @return bool
     */
    public function isThreadCreator($uid = 0) {
        $out = false;
        if ($uid && is_null($this->data)) {
            $this->loadModel();
        }
        $uid = $uid ? $uid : $this->modx->getLoginUserID('web');
        if (!is_null($this->data)) {
            $creator = (int)$this->data->get($this->getCFGDef('threadCreatorField', 'aid'));
            $out = $uid && $uid == $creator;
        }

        return $out;
    }

    protected function loadModel() {
        $model = $this->getCFGDef('contextModel', '\\modResource');
        $thread = (int)$this->getCFGDef('thread');
        if ($model && $thread && class_exists($model)) {
            $this->data = new $model($this->modx);
            $this->data->edit($thread);
        }
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
