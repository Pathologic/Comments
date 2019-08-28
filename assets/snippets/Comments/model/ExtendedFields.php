<?php namespace Comments;
use APIhelpers;

/**
 * Class ExtendedFields
 * @package Comments
 */
Trait ExtendedFields {
   protected $extended_fields = [];
   protected $extended_fields_table = 'comments_extended_fields';

    /**
     * @param int $commentId
     * @param array $names
     * @return array
     */
    protected function loadExtendedFields($commentId) {
        $out = [];
        $commentId = (int)$commentId;
        $where = 'WHERE `id`=' . $commentId;
        $fields = $this->getExtendedFields();
        if (!empty($fields)) {
            $_fields = APIhelpers::sanitarIn($fields);
            $where .= " AND `name` IN ({$_fields})";
        }
        $q = $this->query("SELECT `name`, `value` FROM {$this->makeTable($this->extended_fields_table)} {$where}");
        while ($row = $this->modx->db->getRow($q)) {
            $out[$row['name']] = $row['value'];
        }
        if (empty($fields) && !empty($out)) {
            $this->setExtendedFields(array_keys($out));
        }

        return $out;
    }

    /**
     * @param $commentId
     * @return bool
     */
    protected function saveExtendedFields($commentId) {
        $out = false;
        $commentId = (int)$commentId;
        if (!$commentId) return $out;
        $names = $this->getExtendedFields();
        $values = [];
        $delete = [];
        foreach ($names as $field) {
            $value = $this->get($field);
            if ($value == '') {
                $delete[] = $field;
                continue;
            }
            $field = $this->modx->db->escape($field);
            $value = $this->modx->db->escape($value);
            $values[] = "({$commentId}, '{$field}', '{$value}')";
        }
        if (!empty($values)) {
            $out = true;
            $values = implode(',', $values);
            $this->query("INSERT INTO {$this->makeTable($this->extended_fields_table)} (`id`, `name`, `value`) VALUES {$values} ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        }
        if (!empty($deleted)) {
            $deleted = APIhelpers::sanitarIn($deleted);
            $this->query("DELETE FROM {$this->makeTable($this->extended_fields_table)} WHERE `id`={$commentId} AND `name` IN ({$deleted})");
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function getExtendedFields() {
        return $this->extended_fields;
    }

    /**
     * @param array $names
     */
    public function setExtendedFields($names = []) {
        if (!empty($names) && is_array($names)) {
            $names = array_unique($names);
            foreach ($names as $name) {
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                    $this->extended_fields[] = $name;
                }
            }
        }

        return $this;
    }
}
