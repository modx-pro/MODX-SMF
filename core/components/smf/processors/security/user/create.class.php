<?php

if (!class_exists('modUserCreateProcessor')) {
	require MODX_CORE_PATH . 'model/modx/processors/security/user/create.class.php';
}

class cmfUserCreateProcessor extends modUserCreateProcessor {

	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return defined('SMF');
	}


	/**
	 * @return bool
	 */
	public function beforeSet() {
		$this->setProperty('passwordnotifymethod', 's');
		$this->setProperty('passwordgenmethod', 'none');
		$this->setProperty('specifiedpassword', $this->getProperty('password'));
		$this->setProperty('confirmpassword', $this->getProperty('password'));

		return parent::beforeSet();
	}

}

return 'cmfUserCreateProcessor';