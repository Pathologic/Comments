<?php

/**
 * Class RuntimeSharedSettings
 * @property DocumentParser $modx
 */
class RuntimeSharedSettings  {
    protected static $instance;
    protected $modx;
    public $table = 'rtss';

    /**
     * @param DocumentParser $modx
     * @return RuntimeSharedSettings
     */
    public static function getInstance (DocumentParser $modx)
    {
        if (null === self::$instance) {
            self::$instance = new self($modx);
        }

        return self::$instance;
    }

    /**
     * ThreadsMeta constructor.
     * @param DocumentParser $modx
     */
    private function __construct (DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->table = $this->modx->getFullTableName($this->table);
    }

    /**
     *
     */
    private function __clone ()
    {
    }

    /**
     *
     */
    private function __wakeup ()
    {
    }

    /**
     * @param $element - имя набора параметров
     * @param $context - контекст
     * @param array $settings - массив параметров
     * @param array $ignore - массив параметров, которые необходимо исключить из набора
     */
    public function save($element, $context, $settings = array(), $ignore = array()) {
        if (empty($element) || empty($context) || !is_array($settings) || empty($settings)) return;
        $element = $this->modx->db->escape($element);
        $context = $this->modx->db->escape($context);
        if (!empty($ignore)) {
            foreach ($ignore as $key) {
                unset($settings[$key]);
            }
        }
        $settings = $this->modx->db->escape(json_encode($settings));
        $this->modx->db->query("INSERT IGNORE INTO {$this->table} (`element`, `context`, `settings`) VALUES ('{$element}', '{$context}', '{$settings}') ON DUPLICATE KEY UPDATE `settings` = '{$settings}'");
    }

    /**
     * @param $element - имя набора параметров
`    * @param $context - контекст
     * @param $ignore - массив параметров, которые необходимо исключить из набора
     * @return array
     */
    public function load($element, $context, $ignore = array()) {
        $out = array();
        $element = $this->modx->db->escape($element);
        $context = $this->modx->db->escape($context);
        $q = $this->modx->db->query("SELECT `settings` FROM {$this->table} WHERE `element` = '{$element}' AND `context` = '{$context}'");
        if ($result = $this->modx->db->getValue($q)) {
            $out = json_decode($result, true);
        }
        if (!empty($ignore)) {
            foreach ($ignore as $key) {
                unset($out[$key]);
            }
        }

        return is_null($out) ? array() : $out;
    }

    public function createTable() {
        $this->modx->db->query("
            CREATE TABLE IF NOT EXISTS {$this->table} (
            `element` varchar(255) NOT NULL,
            `context` varchar(255) NOT NULL DEFAULT 'site_content',
            `settings` TEXT NOT NULL,
            UNIQUE KEY `ecid` (`element`, `context`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
