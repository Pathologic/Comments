<?php
return;
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$_params = array_merge(array(
    'config'     => 'recent:assets/snippets/Comments/config/',
    'dir'        => 'assets/snippets/Comments/DocLister/',
    'controller' => 'RecentComments',
    'usertype'  => 'web',
    'userFields' => 'createdby'
    ), $params
);

return $modx->runSnippet('DocLister', $_params);
