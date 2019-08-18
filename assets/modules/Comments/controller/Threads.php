<?php namespace Comments\Module\Controller;

use DocumentParser;

/**
 * Class Actions
 * @package Comments\Module
 */
class Threads extends Comments {
    protected $modx = null;
    protected $data = null;
    /**
     * Actions constructor.
     * @param DocumentParser $modx
     */
    public function __construct (DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    public function listing() {

    }
}
