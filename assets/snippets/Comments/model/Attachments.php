<?php namespace Comments\Traits;

use Comments\Files;

/**
 * Trait Attachments
 * @package Comments
 */
Trait Attachments {
    protected $attachments = [];
    protected $attachments_table = 'comments_attachments';
    protected $files_table = 'comments_files';

    /**
     * @param int $commentId
     * @param array $names
     * @return array
     */
    protected function loadAttachments($commentId) {
        $commentId = (int)$commentId;
        $out = $this->attachments = [];
        $files = new Files($this->modx);
        $q = $this->query("SELECT `f`.* FROM {$this->makeTable($this->files_table)} `f` LEFT JOIN {$this->makeTable($this->attachments_table)} `a` ON `f`.`id` = `a`.`attachment` AND `a`.`comment` = {$commentId} WHERE NOT ISNULL(`a`.`comment`)");
        while ($row = $this->modx->db->getRow($q)) {
            $row['thumb'] = $files->getThumbPath($row['file']);
            $out[$row['id']] = $row;
        }
        $this->attachments = $out;

        return $out;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function saveAttachments($commentId, array $attachments)
    {
        $current = array_keys($this->getAttachments());
        $files = new Files($this->modx);
        $new = $files->getIdsByHash($attachments);
        $delete = array_diff($current, $new);
        $insert = array_diff($new, $current);
        if ($delete) {
            $delete = implode(',', $delete);
            $this->query("DELETE FROM {$this->makeTable($this->attachments_table)} WHERE `attachment` IN ({$delete}) AND `comment` = {$commentId}");
        }
        if ($insert) {
            $_insert = [];
            foreach ($insert as $id) {
                $_insert[] = "({$commentId}, {$id})";
            }
            $_insert = implode(',', $_insert);
            $this->query("INSERT IGNORE INTO {$this->makeTable($this->attachments_table)} (`comment`, `attachment`) VALUES {$_insert} ");
        }
        $this->loadAttachments($commentId);
    }
}
