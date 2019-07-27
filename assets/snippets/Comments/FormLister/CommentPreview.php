<?php namespace FormLister;
use DocumentParser;

/**
 * Class CommentPreview
 * @package FormLister
 */
class CommentPreview extends Core {
    /*
     * @var \Comments\Comments $comments
     */
    protected $comments = null;

    /**
     * Core constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct (DocumentParser $modx, $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $this->comments = $this->loadModel(
            $this->getCFGDef('model', '\Comments\Comments'),
            $this->getCFGDef('modelPath', 'assets/snippets/Comments/model/Comments.php')
        );
    }

    /**
     * @return bool
     */
    public function isSubmitted ()
    {
        return true;
    }

    /**
     * Обработка формы, определяется контроллерами
     *
     * @return mixed
     */
    public function process ()
    {
        $context = $this->getCFGDef('context', 'site_content');
        $thread = (int)$this->getField('thread', 0);
        $parent = (int)$this->getField('parent', 0);
        $this->setField('rawcontent', $this->getField('comment'));
        $fields = $this->getFormData('fields');
        $fields['content'] = '';
        $fields['parent'] = $parent;
        $fields['thread'] = $thread;
        if (!empty($context) && $thread) {
            $this->comments->create($fields)->preview();
        }
        $this->setField('content', $this->comments->get('content'));
        $this->setFormStatus(true);

        return $this;
    }
}
