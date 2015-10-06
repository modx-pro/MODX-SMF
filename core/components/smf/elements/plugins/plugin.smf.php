<?php

$MODX_SMF = $modx->getService('modx_smf', 'MODX_SMF', $modx->getOption('smf_core_path', null, $modx->getOption('core_path') . 'components/smf/') . 'model/');

if (method_exists($MODX_SMF, $modx->event->name)) {
	/**@var array $scriptProperties */
	$MODX_SMF->{$modx->event->name}($scriptProperties);
}