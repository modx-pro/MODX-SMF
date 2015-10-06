<?php

$settings = array();

$tmp = array(
	'path' => array(
		'xtype' => 'textfield',
		'value' => '{base_path}forum/',
		'area' => 'smf_main',
	),
	'forced_sync' => array(
		'xtype' => 'combo-boolean',
		'value' => false,
		'area' => 'smf_main',
	),
	'user_contexts' => array(
		'xtype' => 'textfield',
		'value' => '',
		'area' => 'smf_user',
	),
	'user_groups' => array(
		'xtype' => 'textfield',
		'value' => '',
		'area' => 'smf_user',
	),

);

foreach ($tmp as $k => $v) {
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	$setting->fromArray(array_merge(
		array(
			'key' => 'smf_' . $k,
			'namespace' => PKG_NAME_LOWER,
		), $v
	), '', true, true);

	$settings[] = $setting;
}

unset($tmp);
return $settings;
