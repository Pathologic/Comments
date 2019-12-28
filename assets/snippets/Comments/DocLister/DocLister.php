<?php namespace Comments\Traits;
use Comments\Moderation;

/**
 * Trait CommentsTrait
 */
trait DocLister
{
    protected $context = 'site_content';
    public $moderation = null;
    protected $rating = [];
    protected $rated = [];

    protected function initModeration ()
    {
        if (!$this->getCFGDef('disableModeration', 0)) {
            $this->moderation = new Moderation($this->modx, array(
                'moderatedByThreadCreator' => $this->getCFGDef('moderatedByThreadCreator', 0),
                'threadCreatorField'       => $this->getCFGDef('threadCreatorField', 'aid'),
                'contextModel'             => $this->getCFGDef('contextModel', '\\modResource'),
                'thread'                   => $this->getCFGDef('thread')
            ));
        }
    }

    /**
     * @param string $context
     */
    public function setContext ($context = 'site_content')
    {
        if (!empty($context) && is_scalar($context)) {
            $this->context = $context;
        }
    }

    /**
     * @return string
     */
    public function getContext ()
    {
        return $this->context;
    }

    /**
     * @param $item
     * @return bool
     */
    public function isEditable ($item)
    {
        $out = false;
        $uid = $this->modx->getLoginUserID('web');
        $editTime = $this->getCFGDef('editTime', 180);
        $commentTime = (int)($this->getTimeStart()) + $this->modx->getConfig('server_offset_time') - strtotime($item['createdon']);
        if ($uid && $uid == $item['createdby'] && ($editTime == 0 || $editTime > $commentTime)) {
            $out = $editTime == 0 ? true : $editTime - $commentTime;
        }

        return $out;
    }

    /**
     * @param int $commentId
     * @return bool
     */
    public function isRated ($commentId)
    {
        return in_array($commentId, $this->rated);
    }

    /**
     * @param $item
     * @return array
     */
    public function getClasses ($item)
    {
        $classes = array();
        $classes[] = $item['published'] ? $this->getCFGDef('publishedClass',
            'published') : $this->getCFGDef('unpublishedClass', 'unpublished');
        if ($item['deleted']) {
            $classes[] = $this->getCFGDef('deletedClass', 'deleted');
        }
        if ($item['updatedby']) {
            $classes[] = $this->getCFGDef('updatedClass', 'updated');
        }
        if (!$item['createdby']) {
            $classes[] = $this->getCFGDef('guestClass', 'guest');
        } elseif ($this->isThreadCreator($item['createdby'])) {
            $classes[] = $this->getCFGDef('authorClass', 'author');
        } elseif ($item['createdby'] == '-1') {
            $classes[] = $this->getCFGDef('adminClass', 'admin');
        }

        return $classes;
    }

    /**
     * @return bool
     */
    public function isNotGuest ()
    {
        $uid = $this->modx->getLoginUserID('web');
        $disableGuests = $this->getCFGDef('disableGuests', 1);

        return ($uid || !$disableGuests);
    }

    /**
     * @return bool
     */
    public function isModerator ()
    {
        return !is_null($this->moderation) && $this->moderation->isModerator();
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function hasPermission ($permission = '')
    {
        return !is_null($this->moderation) && $this->moderation->hasPermission($permission);
    }

    /**
     * @param int $uid
     * @return bool
     */
    public function isThreadCreator ($uid = 0)
    {
        return !is_null($this->moderation) && $this->moderation->isThreadCreator($uid);
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getExtendedFields() {
        $out = array();
        $names = $this->getCFGDef('extendedFields');
        $names = $this->sanitarIn($names);
        $ids = array_keys($this->_docs);
        if ($ids && $names) {
            $ids = implode(',', $ids);
            $q = $this->dbQuery("SELECT * FROM {$this->getTable('comments_extended_fields')} WHERE `id` IN ({$ids}) AND `name` IN ({$names})");
            while ($row = $this->modx->db->getRow($q)) {
                $out[$row['id']][$row['name']] = $row['value'];
            }
        }

        return $out;
    }

    /**
     * @return DocLister
     */
    public function getRating() {
        $rating = $this->extCache->load('comments_rating');
        if ($rating === false) {
            $ids = array_keys($this->_docs);
            if ($ids) {
                $ids = implode(',', $ids);
                $q = $this->dbQuery("SELECT * FROM {$this->getTable('comments_rating')} WHERE `comment` IN ({$ids})");
                while ($row = $this->modx->db->getRow($q)) {
                    $id = $row['comment'];
                    unset($row['comment']);
                    $row['count'] = $row['like'] - $row['dislike'];
                    if ($row['count'] > 0) {
                        $row['count'] = '+' . $row['count'];
                    }
                    $this->rating[$id] = $row;
                }
                $this->extCache->save($this->rating, 'comments_rating');
            }
        } else {
            $this->rating = $rating;
        }

        return $this;
    }

    /**
     * @return DocLister
     */
    public function getRated() {
        $ids = array_keys($this->rating);
        $uid = $this->modx->getLoginUserID('web');
        if ($uid && $ids) {
            $ids = implode(',', $ids);
            $q = $this->dbQuery("SELECT `comment` FROM {$this->getTable('comments_rating_log')} WHERE `comment` IN ({$ids}) AND `uid`={$uid}");
            while ($row = $this->modx->db->getRow($q)) {
                $this->rated[] = $row['comment'];
            }
        }

        return $this;
    }
}
