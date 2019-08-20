<?php namespace Comments;

use DocumentParser;
use Helpers\Lexicon;

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
    protected $lexicon = null;

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
        $this->lexicon = new Lexicon($modx, array(
            'langDir' => 'assets/modules/Comments/lang/',
            'lang'    => $this->modx->getConfig('lang_code')
        ));
        $this->lexicon->fromFile('module');
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
            'connector'   => $this->modx->getConfig('site_url') . 'assets/modules/Comments/ajax.php',
            'theme'       => $this->modx->getConfig('manager_theme'),
            'site_url'    => $this->modx->getConfig('site_url'),
            'manager_url' => MODX_MANAGER_URL,
            'lang'        => $this->modx ->getConfig('lang_code')
        );
        $output = $this->DLTemplate->parseChunk($this->tpl, $ph, false, true);
        $output = $this->lexicon->parse($output);

        return $output;
    }
}
