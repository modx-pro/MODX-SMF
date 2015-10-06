<?php

if (!class_exists('modUserDeleteProcessor')) {
	require MODX_CORE_PATH . 'model/modx/processors/security/user/delete.class.php';
}

class cmfUserDeleteProcessor extends modUserDeleteProcessor {

	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return defined('SMF');
	}

}

return 'cmfUserDeleteProcessor';