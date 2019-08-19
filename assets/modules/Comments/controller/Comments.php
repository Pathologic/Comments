<?php namespace Comments\Module\Controller;

use APIhelpers;
use Comments\Comments as CommentsModel;
use Comments\Module\CommentsDocLister;
use DocumentParser;
use Helpers\Lexicon;

/**
 * Class Actions
 * @package Comments\Module
 */
class Comments
{
    protected $modx = null;
    protected $data = null;
    protected $lexicon = null;
    protected $result = array();
    protected $dlParams = [
        'summary'    => 'notags,len:150',
        'usertype'   => 'web',
        'dotSummary' => 1
    ];
    protected $flParams = [
        'formid'            => 'comment-edit',
        'config'            => 'edit:assets/modules/Comments/config/',
        'api'               => 2,
        'apiFormat'         => 'array',
        'dir'               => 'assets/snippets/Comments/FormLister/',
        'controller'        => 'Comments',
        'skipPrerender'     => 'true'
    ];

    /**
     * Actions constructor.
     * @param DocumentParser $modx
     */
    public function __construct (DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->data = new CommentsModel($modx);
        $this->lexicon = new Lexicon($modx, array(
            'langDir' => 'assets/snippets/Comments/lang/',
            'lang'    => $this->modx->getConfig('lang_code')
        ));
        $this->lexicon->fromFile('actions');
    }

    public function listing ()
    {
        $cfg = $this->dlParams;
        if (!empty($_POST['sort']) && is_scalar($_POST['sort'])) {
            $cfg['sortBy'] = $_POST['sort'];
        }
        if (!empty($_POST['order']) && is_scalar($_POST['order'])) {
            $cfg['sortDir'] = $_POST['order'];
        }

        $this->setResult($this->runDocLister($cfg));
    }

    public function publish ()
    {
        $this->callModelMethod('publish');
    }

    public function unpublish ()
    {
        $this->callModelMethod('unpublish');
    }

    public function delete ()
    {
        $this->callModelMethod('delete');
    }

    public function undelete ()
    {
        $this->callModelMethod('undelete');
    }

    public function remove ()
    {
        $this->callModelMethod('remove', 'actions.error_remove');
    }

    /**
     * @param $property
     */
    protected function callModelMethod ($method, $defaultErrorMessage = 'actions.error_update')
    {
        $ids = $this->getRequest('ids', false);
        if ($ids && method_exists($this->data, $method)) {
            $status = $this->data->$method($ids, true, true) !== false;
            $messages = $this->data->getMessages();
            if ($status === false && empty($messages)) {
                $messages = $this->lexicon->get($defaultErrorMessage);
            }
            $this->setResult([
                'status'   => $status,
                'messages' => $messages
            ]);
        } else {
            $this->setResult(false, $this->lexicon->get($defaultErrorMessage));
        }
    }

    public function edit ()
    {
        $id = (int)$this->getRequest('id', 0, 'is_numeric');
        if ($id) {
            $cfg = $this->flParams;
            $cfg['id'] = $id;
            $cfg['prepare'] = '\\Comments\\Module\\Controller\\Comments::flPrepare';
            $this->setResult($this->modx->runSnippet('FormLister', $cfg));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_update'));
        }
    }

    public function create ()
    {
        $thread = (int)$this->getRequest('thread', 0, 'is_numeric');
        $context = (int)$this->getRequest('context', 'site_content', 'is_scalar');
        if ($thread && $context) {
            $cfg = $this->flParams;
            $cfg['context'] = $context;
            $this->setResult($this->modx->runSnippet('FormLister', $cfg));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_create'));
        }
    }

    public function reply ()
    {
        $parent = (int)$this->getRequest('parent', 0, 'is_numeric');
        if ($parent) {
            $cfg = $this->flParams;
            $this->setResult($this->modx->runSnippet('FormLister', $cfg));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_create'));
        }
    }

    public function preview() {
        $cfg = $this->flParams;
        $cfg = array_merge($cfg, [
            'controller' => 'CommentPreview',
            'dir'        => 'assets/snippets/Comments/FormLister/',
            'formid'     => 'preview',
            'api'        => 1,
            'context'   => $this->getRequest('context', 'site_content', 'is_scalar')
        ]);
        $this->setResult($this->modx->runSnippet('FormLister', $cfg));
    }

    /**
     * @param array $cfg
     * @throws \Exception
     */
    protected function runDocLister ($cfg = array())
    {
        $DocLister = new CommentsDocLister($this->modx, $cfg);

        return $DocLister->getDocs();
    }

    /**
     * @param $out
     * @param $message
     */
    protected function setResult ($out, $message = '')
    {
        if (empty($message)) {
            $this->result = $out;
        } else {
            $this->result = ['status' => false, 'messages' => $message];
        }
    }

    /**
     * @param $key
     * @param null $default
     * @param null $validate
     * @return mixed
     */
    protected function getRequest ($key, $default = null, $validate = null)
    {
        return APIHelpers::getkey($_POST, $key, $default, $validate);
    }

    /**
     * @return array
     */
    public function getResult ()
    {
        return $this->result;
    }

    public static function flPrepare($modx, $data, $FormLister) {
        $comments = $FormLister->comments;
        if ($FormLister->getMode() == 'edit' && $comments->getID()) {
            $users = [];
            $fields = ['createdby', 'updatedby', 'deletedby'];
            foreach ($fields as $field) {
                $uid = $comments->get($field);
                if ($uid > 0) {
                    $users[$field] = $uid;
                }
            }
            if (!empty($users)) {
                $_users = implode(',', $users);
                $q = $modx->db->query("SELECT * FROM {$modx->getFullTableName('web_users')} `u` LEFT JOIN {$modx->getFullTableName('web_user_attributes')} `ua` ON `u`.`id`=`ua`.`internalKey` WHERE `u`.`id` IN ($_users)");
                $_users = array();
                while ($row = $modx->db->getRow($q)) {
                    unset($row['password'], $row['sessionid'], $row['cachepwd'], $row['internalKey']);
                    $_users[$row['id']] = $row;
                }
                foreach ($users as $field => $value) {
                    if (isset($_users[$value])) {
                        $FormLister->setFields(\APIhelpers::renameKeyArr($_users[$value], 'user', $field));
                    }
                }
            }
            $thread = $comments->get('thread');
            $context = $comments->get('context');
            if ($context == 'site_content' && $thread) {
                $doc = new \modResource($modx);
                $doc->edit($thread);
                if ($doc->getID()) {
                    $FormLister->setField('resource', $doc->get('pagetitle'));
                }
            }
        }
    }
}
