<?php
define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once(__DIR__."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
if(!isset($_SESSION['mgrValidated'])){
    die();
}
$modx->invokeEvent('OnManagerPageInit');

include_once MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php';

$out = array();
$cfgFile = 'assets/snippets/Comments/custom/config.php';
$commentsController = '\\Comments\\Module\\Controller\\Comments';
$threadsController = '\\Comments\\Module\\Controller\\Threads';

if (Helpers\FS::getInstance()->checkFile($cfgFile)) {
    $cfg = require(MODX_BASE_PATH . $cfgFile);
    if (is_array($cfg) && !empty($cfg['comments'])) {
        $commentsController = $cfg['comments'];
    }
    if (is_array($cfg) && !empty($cfg['threads'])) {
        $threadsController = $cfg['threads'];
    }
}
$action = (isset($_REQUEST['action']) && is_scalar($_REQUEST['action']) && preg_match('/[a-z]+\/[a-z]+/', $_REQUEST['action'])) ? $_REQUEST['action'] : false;
if ($action !== false) {
    $action = explode('/', $action);
    list($controller, $method) = $action;
    $controller = $controller == 'threads' ? new $threadsController($modx) : new $commentsController($modx);
    if (!empty($method) && method_exists($controller, $method)) {
        call_user_func([$controller, $method]);
        $out = $controller->getResult();
    }
}

echo ($out = is_array($out) ? json_encode($out) : $out);
