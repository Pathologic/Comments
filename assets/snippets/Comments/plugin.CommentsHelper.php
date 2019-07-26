<?php
if (!defined('MODX_BASE_PATH')) die();
$event = $modx->event->name;
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$plugin = new \Comments\Plugin($modx);
if (method_exists($plugin, $event)) {
    call_user_func(array($plugin, $event));
}
