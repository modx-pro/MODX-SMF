<?php

/** @var modX $modx */
/** @var MODX_SMF $MODX_SMF */
if (defined('MODX_API_MODE')) {
	return;
}
$basePath = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
global $modx, $MODX_SMF;

define('MODX_API_MODE', true);
require $basePath . '/index.php';

$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

$MODX_SMF = $modx->getService('modx_smf', 'MODX_SMF', $modx->getOption('smf_core_path', null, $modx->getOption('core_path') . 'components/smf/') . 'model/');


// integrate_profile_save hack for 2.0
if (!empty($_GET['action']) && preg_match('#^profile;area=account;u=(\d+);save#', $_GET['action'], $matches) && !empty($_POST['u'])) {
	global $modSettings, $user_info;
	loadUserSettings();

	if ($user_info['id'] == $matches[1] && $user_info['id'] == $_POST['u']) {
		$data = array(
			'name' => 'real_name',
			'email' => 'email_address',
		);
		foreach ($data as $k => $v) {
			if (isset($_POST[$v]) && $user_info[$k] != $_POST[$v]) {
				$MODX_SMF::smfOnUserUpdate(array($user_info['username']), $v, $_POST[$v]);
			}
		}
	}
}

