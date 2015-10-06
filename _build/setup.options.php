<?php

$exists = $chunks = false;
$output = null;
/**@var array $options */
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
	case xPDOTransport::ACTION_INSTALL:
		$exists = $modx->getObject('modSystemSetting', array('key' => 'smf_path'));
		break;

	case xPDOTransport::ACTION_UPGRADE:
		$exists = $modx->getObject('modSystemSetting', array('key' => 'smf_path'));
		if (!empty($options['attributes']['chunks'])) {
			$chunks = '<ul id="formCheckboxes" style="height:200px;overflow:auto;">';
			foreach ($options['attributes']['chunks'] as $k => $v) {
				$chunks .= '
				<li>
					<label>
						<input type="checkbox" name="update_chunks[]" value="' . $k . '"> ' . $k . '
					</label>
				</li>';
			}
			$chunks .= '</ul>';
		}
		break;

	case xPDOTransport::ACTION_UNINSTALL:
		break;
}

$output = '';

$path = $exists
	? $exists->get('value')
	: '{base_path}forum/';
switch ($modx->getOption('manager_language')) {
	case 'ru':
		$output = 'Пожалуйста, укажите путь к файлам SMF. Можно использовать {base_path} как путь к директории MODX.';
		break;
	default:
		$output = 'Please specify path to the installed SMF. You can use {base_path} as a path to MODX directory.';
}
$output .= '<br>
<input name="path" value="' . $path . '" style="padding:5px;border-radius:5px;border:1px solid #AAA;"/>
<br/><br/>';

/*
if ($chunks) {
	switch ($modx->getOption('manager_language')) {
		case 'ru':
			$output .= 'Выберите чанки, которые нужно <b>перезаписать</b>:<br/>
				<small>
					<a href="#" onclick="Ext.get(\'formCheckboxes\').select(\'input\').each(function(v) {v.dom.checked = true;});">отметить все</a> |
					<a href="#" onclick="Ext.get(\'formCheckboxes\').select(\'input\').each(function(v) {v.dom.checked = false;});">cнять отметки</a>
				</small>
			';
			break;
		default:
			$output .= 'Select chunks, which need to <b>overwrite</b>:<br/>
				<small>
					<a href="#" onclick="Ext.get(\'formCheckboxes\').select(\'input\').each(function(v) {v.dom.checked = true;});">select all</a> |
					<a href="#" onclick="Ext.get(\'formCheckboxes\').select(\'input\').each(function(v) {v.dom.checked = false;});">deselect all</a>
				</small>
			';
	}
	$output .= $chunks;
}
*/

return $output;