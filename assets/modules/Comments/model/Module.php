<?php namespace Comments;

use DocumentParser;

/**
 * Class Module
 * @package Comments
 */
class Module
{
    protected $modx = null;
    protected $params = array();
    protected $DLTemplate = null;
    protected $templatePath = 'assets/modules/Comments/tpl/';
    protected $tpl = '@FILE:module';

    /**
     * Module constructor.
     * @param DocumentParser $modx
     * @param bool $debug
     */
    public function __construct (DocumentParser $modx, $debug = false)
    {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        $data = new Comments($modx);
        $data->createTable();
    }

    /**
     * @return bool|string
     */
    public function render ()
    {
        $this->DLTemplate->setTemplatePath($this->templatePath);
        $this->DLTemplate->setTemplateExtension('tpl');
        $ph = array(
            'connector'   => $this->modx->config['site_url'] . 'assets/modules/Comments/ajax.php',
            'theme'       => $this->modx->config['manager_theme'],
            'site_url'    => $this->modx->config['site_url'],
            'manager_url' => MODX_MANAGER_URL
        );
        $output = $this->DLTemplate->parseChunk($this->tpl, $ph, false, true);

        return $output;
    }
}
