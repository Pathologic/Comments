<?php namespace Comments;

use DocumentParser;
use Exception;
use RuntimeSharedSettings;
use Helpers\Config;
use Helpers\Lexicon;

/**
 * Class Actions
 * @package Comments
 * @property DocumentParser $modx
 * @property Moderation $moderation
 * @property Config $commentsConfig
 * @property Config $formConfig
 * @property Lexicon $lexicon
 * @property string $context
 * @property int $thread
 * @property int $id
 * @property int $parent
 * @property int $lastComment
 */
class Actions
{
    protected $modx;
    protected $moderation;
    protected $commentsConfig;
    protected $formConfig;
    protected $lexicon;
    protected $ahCommentsElement = 'TreeViewComments';
    protected $ahFormElement = 'CommentsForm';
    protected $thread = 0;
    protected $context = 'site_content';
    protected $parent = 0;
    protected $id = 0;
    protected $lastComment = 0;
    protected $result = [];
    public $formConfigOverride = [
        'disableSubmit' => 0,
        'api'           => 2,
        'apiFormat'     => 'array',
        'rtssElement'   => ''
    ];

    /**
     * Actions constructor.
     * @param DocumentParser $modx
     */
    public function __construct (DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->thread = !empty($_POST['thread']) && $_POST['thread'] > 0 ? (int)$_POST['thread'] : 0;
        $this->parent = !empty($_POST['parent']) && $_POST['thread'] > 0 ? (int)$_POST['parent'] : 0;
        $this->id = !empty($_POST['id']) && $_POST['id'] > 0 ? (int)$_POST['id'] : 0;
        $this->lastComment = !empty($_POST['lastComment']) ? (int)$_POST['lastComment'] : 0;
        $this->loadConfig();
        $this->lexicon = new Lexicon($modx, array(
            'langDir' => 'assets/snippets/Comments/lang/',
            'lang'    => $this->getCFGDef('form', 'lang', $this->modx->getConfig('lang_code')),
            'handler' => $this->getCFGDef('form', 'lexiconHandler')
        ));
        $this->lexicon->fromFile('actions');
    }

    /**
     * @return bool
     */
    protected function loadConfig ()
    {
        $ah = RuntimeSharedSettings::getInstance($this->modx);
        $out = true;
        $config = $ah->load($this->ahFormElement, $this->context);
        $this->formConfig = new Config($config);
        $out = $out && !empty($config);
        $config = $ah->load($this->ahCommentsElement, $this->context);
        $this->commentsConfig = new Config($config);
        $out = $out && !empty($config);
        $this->initModeration();

        return $out;
    }

    protected function initModeration() {
        $this->moderation = new Moderation($this->modx, array(
            'moderatedByThreadCreator' => $this->getCFGDef('comments', 'moderatedByThreadCreator', 1),
            'threadCreatorField'       => $this->getCFGDef('comments', 'threadCreatorField', 'aid'),
            'contextModel'             => $this->getCFGDef('comments', 'contextModel', '\\modResource'),
            'thread'                   => $this->thread
        ));
    }

    /*
     * Добавление нового комментария
     */
    public function create ()
    {
        if ($this->thread) {
            $cfg = $this->formConfig->getConfig();
            $cfg['thread'] = $this->thread;
            $cfg['mode'] = 'create';
            $this->setResult($this->modx->runSnippet(
                'FormLister',
                array_merge(
                    $cfg,
                    $this->formConfigOverride
                )
            ));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_create'));
        }
    }

    /*
     * Добавление ответа на существующий комментарий
     */
    public function reply ()
    {
        if ($this->thread && $this->parent) {
            $cfg = $this->formConfig->getConfig();
            $this->setResult($this->modx->runSnippet(
                'FormLister',
                array_merge(
                    $cfg,
                    $this->formConfigOverride
                )
            ));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_create'));
        }
    }

    /*
     * Редактирование комментария пользователем
     */
    public function update ()
    {
        if ($this->thread && $this->id) {
            $cfg = $this->formConfig->getConfig();
            $cfg['id'] = $this->id;
            $this->setResult($this->modx->runSnippet(
                'FormLister',
                array_merge(
                    $cfg,
                    $this->formConfigOverride
                )
            ));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_update'));
        }
    }

    /*
     * Редактирование комментария модератором
     */
    public function edit ()
    {
        if ($this->thread && $this->id) {
            $cfg = $this->formConfig->loadArray($this->getCFGDef('form', 'moderation'));
            $cfg['dir'] = 'assets/snippets/Comments/FormLister/';
            $cfg['controller'] = 'Moderation';
            $cfg['id'] = $this->id;
            $this->setResult($this->modx->runSnippet(
                'FormLister',
                array_merge(
                    $cfg,
                    $this->formConfigOverride
                )
            ));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_update'));
        }
    }

    public function load ()
    {
        if ($this->thread) {
            $cfg = $this->commentsConfig->getConfig();
            $cfg['thread'] = $this->thread;
            $cfg['addWhereList'] = 'c.id > ' . $this->lastComment;
            $cfg['mode'] = 'recent';
            $this->setResult($this->modx->runSnippet('DocLister', $cfg));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error_load'));
        }
    }

    public function loadComment ()
    {
        if ($this->thread && $this->id) {
            $cfg = $this->commentsConfig->getConfig();
            $cfg['thread'] = $this->thread;
            $cfg['addWhereList'] = 'c.id = ' . $this->id;
            $cfg['mode'] = 'recent';
            $this->setResult($this->modx->runSnippet('DocLister', $cfg));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error'));
        }
    }

    public function preview ()
    {
        if ($this->thread) {
            $cfg = array(
                'controller' => 'CommentPreview',
                'dir'        => 'assets/snippets/Comments/FormLister/',
                'formid'     => $this->getCFGDef('form', 'formid'),
                'api'        => 1,
                'filters'    => $this->getCFGDef('form', 'filters')
            );
            $this->setResult($this->modx->runSnippet('FormLister', $cfg));
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error'));
        }
    }

    /**
     * @throws Exception
     */
    public function publish ()
    {
        $this->callModelMethod('publish', 'comments_publish');
    }

    /**
     * @throws Exception
     */
    public function unpublish ()
    {
        $this->callModelMethod('unpublish', 'comments_unpublish');
    }

    /**
     * @throws Exception
     */
    public function delete ()
    {
        $this->callModelMethod('delete', 'comments_delete');
    }

    /**
     * @throws Exception
     */
    public function undelete ()
    {
        $this->callModelMethod('undelete', 'comments_undelete');
    }

    /**
     * @throws Exception
     */
    public function remove ()
    {
        $this->callModelMethod('remove', 'comments_remove', 'actions.error_remove');
    }

    /**
     *
     */
    public function like()
    {
        if ($this->commentsConfig->getCFGDef('rating', 1) && $this->thread && $this->id) {
            $data = Rating::getInstance($this->modx);
            $result = $data->like($this->id, true, true);
            $messages = $data->getMessages();
            if ($result) {
                $out = $data->get($this->id);
                if (!empty($messages)) {
                    $out['messages'] = $messages;
                }
                $out['status'] = true;
            } else {
                $out['status'] = false;
                if (!empty($messages)) {
                    $out['messages'] = $messages;
                }
            }
            $this->setResult($out);
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error'));
        }
    }

    /**
     *
     */
    public function dislike () {
        if ($this->commentsConfig->getCFGDef('rating', 1) && $this->thread && $this->id) {
            $data = Rating::getInstance($this->modx);
            $result = $data->dislike($this->id, true, true);
            $messages = $data->getMessages();
            if ($result) {
                $out = $data->get($this->id);
                if (!empty($messages)) {
                    $out['messages'] = $messages;
                }
                $out['status'] = true;
            } else {
                $out['status'] = false;
                if (!empty($messages)) {
                    $out['messages'] = $messages;
                }
            }
            $this->setResult($out);
        } else {
            $this->setResult(false, $this->lexicon->get('actions.error'));
        }
    }

    public function subscribe() {
        if ($this->thread) {
            Subscriptions::getInstance($this->modx)->subscribe($this->thread, $this->context);
        }
        $this->setResult(['status' => true]);
    }

    public function unsubscribe() {
        if ($this->thread) {
            Subscriptions::getInstance($this->modx)->unsubscribe($this->thread, $this->context);
        }
        $this->setResult(['status' => true]);
    }

    /**
     * @param $method
     * @param $permission
     * @param string $defaultErrorMessage
     */
    protected function callModelMethod($method, $permission, $defaultErrorMessage = 'actions.error_update') {
        if ($this->thread && $this->id) {
            $data = $this->getModel();
            if (method_exists($data, $method) && $this->moderation->hasPermission($permission)) {
                $status = $data->$method($this->id, true, true) !== false;
                $messages = $data->getMessages();
                if ($status === false && empty($messages)) {
                    $messages = $this->lexicon->get($defaultErrorMessage);
                }
                $stat = Stat::getInstance($this->modx)->getStat($this->thread, $this->context);
                $this->setResult(array(
                    'status'   => $status,
                    'messages' => $messages,
                    'count'    => $stat['comments_count']
                ));
            } else {
                $this->setResult(false, $this->lexicon->get('actions.access_denied'));
            }
        } else {
            $this->setResult(false, $this->lexicon->get($defaultErrorMessage));
        }
    }

    /**
     * @return mixed
     */
    protected function getModel() {
        $model = $this->getCFGDef('form', 'model', 'Comments\\Comments');
        
        return new $model($this->modx);
    }

    /**
     * @param $config
     * @param $key
     * @param string $default
     * @return mixed
     */
    protected function getCFGDef ($config, $key, $default = '')
    {
        $config = $config . 'Config';
        $out = $default;
        if (isset($this->$config)) {
            $out = $this->$config->getCFGDef($key, $default);
        }

        return $out;
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
            $this->result = array('status' => false, 'messages' => $message);
        }
    }

    /**
     * @return array
     */
    public function getResult ()
    {
        return $this->result;
    }
}
