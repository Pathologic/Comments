<?php
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$_params = array_merge(array(
    'config'     => 'comments:assets/snippets/Comments/config/',
    'dir'        => 'assets/snippets/Comments/DocLister/',
    'templatePath' => 'assets/snippets/Comments/tpl/',
    'templateExtension' => 'tpl',
    'controller' => 'TreeView',
    'thread'    => $modx->documentIdentifier,
    ), $params
);

return $modx->runSnippet('DocLister', $_params);
