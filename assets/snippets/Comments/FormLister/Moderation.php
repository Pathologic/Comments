<?php namespace FormLister;

use DocumentParser;
use Comments\Moderation as CommentsModeration;

/**
 * Class Moderation
 * @package FormLister
 */
class Moderation extends Core
{
    /*
     * var Comments\Comments $comments
     */
    public $comments = null;
    public $moderation = null;

    /**
     * Core constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct (DocumentParser $modx, array $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $this->comments = $this->loadModel(
            $this->getCFGDef('model', '\Comments\Comments'),
            $this->getCFGDef('modelPath', 'assets/snippets/Comments/model/Comments.php')
        );
        $this->lexicon->config->setConfig(array(
            'langDir' => 'assets/snippets/Comments/lang/'
        ));
        $this->lexicon->fromFile('moderation');
        $this->log('Lexicon loaded', array('lexicon' => $this->lexicon->getLexicon()));
        if ($id = $this->getCFGDef('id', 0)) {
            $this->comments->edit($id);
        }
        $this->initModeration();
    }

    /**
     *
     */
    public function initModeration() {
        $this->moderation = new CommentsModeration($this->modx, array(
            'moderatedByThreadCreator' => $this->getCFGDef('moderatedByThreadCreator', 0),
            'threadCreatorField'       => $this->getCFGDef('threadCreatorField', 'aid'),
            'contextModel'             => $this->getCFGDef('contextModel', '\\modResource'),
            'thread'                   => $this->comments->get('thread')
        ));
    }

    /**
     * @return string|array
     */
    public function render ()
    {
        $allowed = ($this->modx->getLoginUserId('mgr') && $this->getCFGDef('disablePermissionsCheck', 0)) ||
            ($this->modx->getLoginUserId('web') && $this->moderation->hasPermission('comments_edit'));
        $allowed = $allowed && $this->comments->getID();
        if (!$allowed) {
            $this->setValid(false);
            if ($tpl = $this->getCFGDef('skipTpl')) {
                $this->renderTpl = $tpl;
            } else {
                $this->addMessage($this->translate('moderation.access_denied'));
            }
        } elseif (!$this->isSubmitted()) {
            $this->setFields($this->comments->toArray());
        }
        if (!$this->comments->get('createdby')) {
            if ($tpl = $this->getCFGDef('guestFormTpl')) {
                $this->renderTpl = $tpl;
            }
        }

        return parent::render();
    }

    /**
     * Загрузка правил валидации
     * @param string $param
     * @return array
     */
    public function getValidationRules ($param = 'rules')
    {
        $guest = !$this->comments->get('createdby');
        if ($param === 'rules' && $guest) {
            $param = 'guestRules';
        }

        return parent::getValidationRules($param);
    }

    /**
     * Создание комментария
     * @return mixed|void
     */
    public function process ()
    {
        $result = $this->comments->fromArray($this->getFormData('fields'))->save(true, true);
        $extMessages = $this->addMessagesFromModel();
        if ($result) {
            $this->setFields($this->comments->toArray());
            $this->setFormStatus(true);
            if (empty($this->getCFGDef('successTpl')) && !$extMessages) {
                $this->addMessage($this->translate('moderation.comment_saved'));
            }
        } elseif (!$extMessages) {
            $this->addMessage($this->translate('moderation.unable_to_save'));
        }
    }

    /**
     * @return bool
     */
    public function addMessagesFromModel ()
    {
        $messages = $this->comments->getMessages();
        foreach ($messages as $message) {
            $this->addMessage($message);
        }

        return !empty($messages);
    }
}
