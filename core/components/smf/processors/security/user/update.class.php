<?php

if (!class_exists('modUserUpdateProcessor')) {
	require MODX_CORE_PATH . 'model/modx/processors/security/user/update.class.php';
}

class cmfUserUpdateProcessor extends modUserUpdateProcessor {

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
		$properties = $this->getProperties();

		$properties['passwordnotifymethod'] = 's';
		if (empty($properties['username'])) {
			$properties['username'] = $this->object->get('username');
		}
		if (empty($properties['email'])) {
			$properties['email'] = $this->object->Profile->get('email');
		}
		$this->setProperties($properties);

		return parent::beforeSet();
	}

}

return 'cmfUserUpdateProcessor';