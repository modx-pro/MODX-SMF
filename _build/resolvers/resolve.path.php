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
			/** @var MODX_SMF $MODX_SMF */
			$MODX_SMF = $modx->getService('modx_smf', 'MODX_SMF', MODX_CORE_PATH . 'components/smf/model/');
			break;

		case xPDOTransport::ACTION_UNINSTALL:
			/** @var MODX_SMF $MODX_SMF */
			if ($MODX_SMF = $modx->getService('modx_smf', 'MODX_SMF', MODX_CORE_PATH . 'components/smf/model/')) {
				$modx->log(xPDO::LOG_LEVEL_INFO, 'Removing hooks from SMF');
				$MODX_SMF->smfRemoveHooks();
			}
			$path = MODX_CORE_PATH . 'components/smf';
			$modx->log(xPDO::LOG_LEVEL_INFO, 'Removing files in file resolver: ' . $path);

			$cacheManager = $modx->getCacheManager();
			if ($cacheManager && file_exists($path)) {
				if (is_dir($path) && $cacheManager->deleteTree($path, array_merge(array('deleteTop' => true, 'skipDirs' => false, 'extensions' => array()), $options))) {
					$resolved = true;
				}
				elseif (is_file($path) && unlink($path)) {
					$resolved = true;
				}
				else {
					$modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not remove files from path: ' . $path);
				}
			}
			else {
				$modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find files to remove.');
			}
			break;
	}
}
return true;