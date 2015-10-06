<?php

if (!class_exists('modUserActivateMultipleProcessor')) {
	require MODX_CORE_PATH . 'model/modx/processors/security/user/activatemultiple.class.php';
}

class cmfUserActivateMultipleProcessor extends modUserActivateMultipleProcessor {

	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return defined('SMF');
	}

}

return 'cmfUserActivateMultipleProcessor';