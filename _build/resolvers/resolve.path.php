<?php

if ($object->xpdo) {
	/** @var modX $modx */
	$modx =& $object->xpdo;
	/**@var array $options */
	switch ($options[xPDOTransport::PACKAGE_ACTION]) {
		case xPDOTransport::ACTION_INSTALL:
		case xPDOTransport::ACTION_UPGRADE:
			if (!empty($options['path']) && $setting = $modx->getObject('modSystemSetting', 'smf_path')) {
				$path = trim(trim($options['path']), '/');
				$setting->set('value', "{$path}/");
				$setting->save();
				$modx->config['smf_path'] = $setting->get('value');
			}

			// Initialize SMF
			$MODX_SMF = $modx->getService('modx_smf', 'MODX_SMF', MODX_CORE_PATH . 'components/smf/model/');
			break;

		case xPDOTransport::ACTION_UNINSTALL:
			break;
	}
}
return true;