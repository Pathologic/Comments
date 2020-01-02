<?php
include_once(MODX_BASE_PATH . 'assets/snippets/Comments/autoload.php');
$_params = array_merge([
    'config'=>'subscription:assets/snippets/Comments/config/',
], $params
);
$config = new \Helpers\Config($_params);
$config->loadConfig($_params['config']);
$thread = $config->getCFGDef($thread, $modx->documentIdentifier);
$context = $config->getCFGDef($context, 'site_content');
$uid = $modx->getLoginUserID('web');
$hasSubscription = \Comments\Subscriptions::getInstance($modx)->hasSubscription($thread, $context);
if ($uid && ($tpl = $config->getCFGDef('tpl'))) {
    $DLTemplate = DLTemplate::getInstance($modx);
    $_templatePath = $DLTemplate->getTemplatePath();
    $_templateExtension = $DLTemplate->getTemplateExtension();
    $DLTemplate->setTemplatePath($config->getCFGDef('templatePath'))->setTemplateExtension($config->getCFGDef('templateExtension'));
    $out = $DLTemplate->parseChunk($tpl, ['hasSubscription' => $hasSubscription]);
    $DLTemplate->setTemplatePath($_templatePath)->setTemplateExtension($_templateExtension);
} else {
    $out = $hasSubscription;
}

return $out;
