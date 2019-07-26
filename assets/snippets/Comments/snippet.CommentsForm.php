<?php
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$_params = array_merge(array(
    'config'=>'form:assets/snippets/Comments/config/',
    'dir'=>'assets/snippets/Comments/FormLister/',
    'controller'=>'Comments',
    'formid'=>'comments-form',
    'templatePath' => 'assets/snippets/Comments/tpl/',
    'templateExtension' => 'tpl',
    'thread' => $modx->documentIdentifier
    ), $params
);

return $modx->runSnippet('FormLister', $_params);
