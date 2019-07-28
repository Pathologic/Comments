<?php
define('MODX_API_MODE', true);
include_once(__DIR__."/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}

$modx->invokeEvent("OnWebPageInit");

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') || strpos($_SERVER['HTTP_REFERER'],$modx->config['site_url']) !== 0 || empty($_POST['action']) || !is_scalar($_POST['action'])){
    $modx->sendErrorPage();
}
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$controller = new \Comments\Actions($modx);
$method = $_POST['action'];
$out = array();
if (method_exists($controller, $method)) {
    call_user_func(array($controller, $method));
    $out = $controller->getResult();
}

echo is_array($out) ? json_encode($out) : $out;
