<?php namespace FormLister;

use DocumentParser;
use RuntimeSharedSettings;

/**
 * Class Comments
 * @package FormLister
 */
class Comments extends Core
{
    use SubmitProtection;
    /*
     * var Comments\Comments $comments
     */
    public $comments = null;
    protected $mode = 'create';
    public $forbiddenFields = array(
        'createdby',
        'updatedby',
        'deletedby',
        'createdon',
        'deletedon',
        'updatedon',
        'thread',
        'context'
    );

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
        $comment = $this->getCFGDef('id', 0);
        if ($extendedFields = $this->getCFGDef('extendedFields')) {
            $extendedFields = $this->config->loadArray($extendedFields);
            if (!empty($extendedFields)) {
                $this->comments->setExtendedFields($extendedFields);
            }
        }
        if ($comment) {
            $this->mode = 'edit';
            $this->comments->edit($comment);
        } else {
            $this->mode = 'create';
        }
        $this->lexicon->fromFile('form');
        $this->lexicon->fromFile('comments', $this->getCFGDef('lang', $this->modx->getConfig('lang_code')),
            $this->getCFGDef('langDir', 'assets/snippets/Comments/lang/'));
        $this->log('Lexicon loaded', array('lexicon' => $this->lexicon->getLexicon()));

    }

    /**
     * Загружает класс капчи
     */
    public function initCaptcha ()
    {
        $useCaptchaForGuestsOnly = $this->getCFGDef('useCaptchaForGuestsOnly', 1);
        $uid = $this->modx->getLoginUserID('web');
        $flag = (!$useCaptchaForGuestsOnly)
            || (!$uid && $useCaptchaForGuestsOnly);
        $flag = $flag && !$this->isManagerMode();

        return $flag ? parent::initCaptcha() : $this;
    }


    /**
     * Сохраняет настройки
     */
    protected function saveSettings ()
    {
        $rtss = RuntimeSharedSettings::getInstance($this->getMODX());
        $rtss->save(
            $this->getCFGDef('rtssElement', 'CommentsForm'),
            $this->getCFGDef('context', 'site_content'),
            $this->config->getConfig()
        );
    }

    /**
     * @return string|array
     */
    public function render ()
    {
        if (!$this->isSubmitted() && !$this->isManagerMode() && $this->getCFGDef('rtss', 1)) {
            $this->saveSettings();
        } elseif ($this->checkSubmitLimit() || $this->checkSubmitProtection()) {
            $this->renderForm();
        }

        return $this->mode == 'create' ? $this->renderCreate() : $this->renderEdit();
    }

    /**
     * @return string|array
     */
    protected function renderCreate ()
    {
        $parent = (int)$this->getField('parent', 0);
        $this->setField('parent', $parent);
        if (!$this->isManagerMode() && !$this->isGuestEnabled() && $this->isGuest()) {
            $this->setValid(false);
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
        if ($param === 'rules') {
            $managerMode = $this->isManagerMode();
            if ((!$managerMode && $this->isGuestEnabled() && $this->isGuest() && $param === 'rules') || ($managerMode && $this->mode == 'edit' && !$this->comments->get('createdby'))) {
                $param = 'guestRules';
            }
            if (!$managerMode && $this->mode == 'edit' && $this->getCFGDef('editRules')) {
                $param = 'editRules';
            }
        }

        return parent::getValidationRules($param); // TODO: Change the autogenerated stub
    }


    /**
     * @return string|array
     */
    protected function renderEdit ()
    {
        $uid = $this->modx->getLoginUserID('web');
        $flag = false;
        $managerMode = $this->isManagerMode();
        if (!$uid && !$managerMode) {
            $this->addMessage($this->translate('comments.only_users_can_edit'));
        } elseif ($this->comments->getID()) {
            $fields = $this->comments->toArray();
            if ($managerMode) {
                $flag = true;
            } elseif ($fields['createdby'] != $uid || $fields['deleted'] || !$fields['published']) {
                $this->addMessage($this->translate('comments.cannot_edit'));
            } elseif (count($this->comments->getBranchIds($fields['id'])) > 1) {
                $this->addMessage($this->translate('comments.comment_is_answered'));
            } elseif (!$this->checkEditTime($fields['createdon'])) {
                $this->addMessage($this->translate('comments.edit_time_expired'));
            } else {
                $flag = true;
            }
            if ($flag) {
                $this->setFields(array_merge($fields, $this->getFormData('fields')));

                if (!$this->isSubmitted()) {
                    $this->setField('published', $fields['published']);
                    $this->setField('deleted', $fields['deleted']);
                    $this->setField('comment', $this->getField('rawcontent'));
                }
            }
        } else {
            $this->addMessage($this->translate('comments.cannot_edit'));
        }
        $this->setValid($flag);

        return parent::render();
    }

    /**
     * @param $createdon
     * @return bool
     */
    public function checkEditTime ($createdon)
    {
        $editTime = $this->getCFGDef('editTime', 180);
        $out = $editTime == 0 || time() + $this->modx->getConfig('server_offset_time') - strtotime($createdon) < $editTime;

        return $out;
    }

    /**
     * Обработка формы, определяется контроллерами
     *
     * @return mixed
     */

    public function process ()
    {
        return $this->mode == 'create' ? $this->processCreate() : $this->processEdit();
    }

    /**
     * Создание комментария
     * @return mixed|void
     */
    public function processCreate ()
    {
        $uid = $this->modx->getLoginUserID('web');
        $result = false;
        $managerMode = $this->isManagerMode();
        if (!$this->isGuestEnabled() && !$uid && !$managerMode) {
            $this->addMessage($this->translate('comments.only_users_can_create'));
        } else {
            $context = $this->getCFGDef('context', 'site_content');
            $thread = (int)$this->getField('thread', 0);
            $parent = (int)$this->getField('parent', 0);
            $fields = $this->filterFields($this->getFormData('fields'), $this->allowedFields, $this->forbiddenFields);
            $fields['parent'] = $parent;
            $fields['thread'] = $thread;
            $fields['context'] = $context;
            if ($managerMode) {
                $fields['createdby'] = -1;
            } else {
                $fields['createdby'] = $uid;
            }
            if (!empty($context) && $thread) {
                $result = $this->comments->create($fields)->save(true, true);
            }
        }
        $extMessages = $this->addMessagesFromModel();
        if ($result) {
            $this->setFields($this->comments->toArray());
            $this->setFormStatus(true);
            $this->saveFormFields();
            if (empty($this->getCFGDef('successTpl')) && !$extMessages) {
                $this->addMessage($this->translate('comments.comment_saved'));
            }
        } elseif (!$extMessages) {
            $this->addMessage($this->translate('comments.unable_to_save'));
        }
    }

    /**
     * Редактирование комментария
     * @return mixed|void
     */
    public function processEdit ()
    {
        $result = false;
        $uid = $this->modx->getLoginUserID('web');
        $managerMode = $this->isManagerMode();
        if (!$uid && !$managerMode) {
            $this->addMessage($this->translate('comments.only_users_can_edit'));
        } else {
            if (!empty($this->allowedFields)) {
                $this->allowedFields[] = 'comment';
            }
            $fields = $this->filterFields($this->getFormData('fields'), $this->allowedFields, $this->forbiddenFields);
            if ($managerMode) {
                $fields['updatedby'] = -1;
            } else {
                $fields['updatedby'] = $uid;
            }
            $result = $this->comments->fromArray($fields)->save(true, true);
        }
        if ($result) {
            $this->setFormStatus(true);
            $this->setFields($this->comments->toArray());
            $this->setField('comment', $this->comments->get('content'));
            if (empty($this->getCFGDef('successTpl'))) {
                $this->addMessage($this->translate('comments.comment_saved'));
            }
        } else {
            $this->addMessage($this->translate('comments.unable_to_save'));
        }
    }

    /**
     * @return bool
     */
    protected function isManagerMode ()
    {
        return defined('IN_MANAGER_MODE') && IN_MANAGER_MODE === true;
    }

    /**
     * @return string
     */
    public function getMode ()
    {
        return $this->mode;
    }

    /**
     * @return bool
     */
    public function isGuest ()
    {
        return !$this->modx->getLoginUserID('web');
    }

    /**
     * @return bool
     */
    public function isGuestEnabled ()
    {
        return !(int)$this->getCFGDef('disableGuests', 1);
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

    public function saveFormFields ()
    {
        $store = 'store_' . $this->getFormId();
        $fields = $this->config->loadArray($this->getCFGDef('saveFormData', 'name,email'));
        foreach ($fields as $field) {
            $_SESSION[$store][$field] = $this->getField($field);
        }
    }
}
