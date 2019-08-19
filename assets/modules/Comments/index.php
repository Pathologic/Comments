<?php
include_once (MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || empty($modx) || !($modx instanceof \DocumentParser)) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('exec_module')) {
    header("location: " . $modx->getManagerPath() . "?a=106");
}
$cfgFile = 'assets/modules/Comments/custom/config.php';
$cfg = [
    'module' => '\\Comments\\Module'
];
if (Helpers\FS::getInstance()->checkFile($cfgFile)) {
    $cfg = require(MODX_BASE_PATH . $cfgFile);
}
if (is_array($cfg) && !empty($cfg['module'])) {
    $module = new $cfg['module']($modx);
}

echo $module->render();
