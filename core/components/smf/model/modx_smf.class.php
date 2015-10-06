<?php

/**
 * The base class for SMF.
 */
class MODX_SMF {
	/** @var modX $modx */
	public $modx;
	/** @var modUser $_user */
	private $_user = null;
	/** @var modUserProfile $_profile */
	private $_profile = null;


	/**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('smf_core_path', $config, $this->modx->getOption('core_path') . 'components/smf/');
		//$assetsUrl = $this->modx->getOption('smf_assets_url', $config, $this->modx->getOption('assets_url') . 'components/smf/');

		$this->config = array_merge(array(
			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'processorsPath' => $corePath . 'processors/',
			'controllersPath' => $corePath . 'controllers/',
		), $config);

		$this->modx->lexicon->load('smf:default');
		$this->_loadSMF();
	}


	/**
	 * @param $action
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function runProcessor($action, array $data) {
		$this->modx->error->reset();

		return $this->modx->runProcessor($action, $data, array(
			'processors_path' => $this->config['processorsPath'],
		));
	}


	/** MODX functions */


	/**
	 * @param $username
	 *
	 * @return null|object
	 */
	public function addUserToMODX($username) {
		if (!$this->modx->getOption('smf_forced_sync')) {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not add user \"{$username}\" to MODX because another one is existing and \"smf_forced_sync\" is disabled.");

			return null;
		}

		if ($data = smfapi_getUserByUsername($username)) {
			$create = array(
				'username' => $data['member_name'],
				'password' => sha1(rand() + time()),
				'fullname' => @$data['real_name'],
				'email' => @$data['email_address'],
				'active' => @$data['is_activated'],
				'createdon' => @$data['date_registered'],
				'dob' => @$data['birthdate'] != '0000-00-00'
					? @$data['birthdate']
					: 0,
			);
			/** @var modProcessorResponse $response */
			$response = $this->runProcessor('security/user/create', $create);
			if ($response->isError()) {
				$this->modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not add missing user \"{$username}\" to MODX: " . print_r($response->getAllErrors(), true));
			}
			elseif ($user = $this->modx->getObject('modUser', array('username' => $data['username']))) {
				return $user;
			}
		}

		return null;
	}


	/**
	 * @param $username
	 *
	 * @return int|bool
	 */
	public function addUserToSMF($username) {
		/*
		if (smfapi_getUserByUsername($username) && !$this->modx->getOption('smf_forced_sync')) {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not add user \"{$username}\" to SMF because another one is existing and \"smf_forced_sync\" is disabled.");

			return false;
		}
		*/
		$password = !empty($_REQUEST['specifiedpassword']) && !empty($_REQUEST['confirmpassword']) && $_REQUEST['specifiedpassword'] == $_REQUEST['confirmpassword']
			? $_REQUEST['specifiedpassword']
			: '';

		/** @var modUser $user */
		if ($user = $this->modx->getObject('modUser', array('username' => $username))) {
			/** @var modUserProfile $profile */
			$profile = $user->getOne('Profile');
			$create = array(
				'member_name' => $user->username,
				'email' => $profile->email,
				'password' => !empty($password)
					? $password
					: sha1(rand() + time()),
				'require' => $user->active && !$profile->blocked
					? 'nothing'
					: '',
				'real_name' => !empty($profile->fullname)
					? $profile->fullname
					: $user->username,
				'date_registered' => !empty($user->createdon)
					? $user->createdon
					: time(),
				'birthdate' => !empty($profile->dob)
					? $profile->dob
					: '0000-00-00',
				'website_url' => $profile->website,
				'location' => $profile->city,
			);

			$response = smfapi_registerMember($create);
			if (is_int($response)) {
				return $response;
			}
			elseif (is_array($response)) {
				$this->modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not add missing user \"{$username}\" {$this->modx->event->name} to SMF: " . print_r($response, true));
			}
		}

		return false;
	}


	/**
	 * @param array $data
	 */
	public function OnUserBeforeSave(array $data) {
		if (!defined('SMF') || SMF != 'API' || $data['mode'] != 'upd') {
			return;
		}
		/** @var modUser $user */
		$user = $data['user'];
		if (!$user || !($user instanceof modUser)) {
			return;
		}

		// Save current user for update
		$this->_user = $this->modx->getObject('modUser', $user->id);
		$this->_profile = $this->_user->getOne('Profile');
	}


	/**
	 * @param array $data
	 */
	public function OnUserSave(array $data) {
		if (!defined('SMF') || SMF != 'API') {
			return;
		}
		/** @var modUser $user */
		$user = $data['user'];
		if (!$user || !($user instanceof modUser)) {
			return;
		}
		/** @var modUserProfile $profile */
		$profile = $user->getOne('Profile');

		$password = !empty($_REQUEST['specifiedpassword']) && !empty($_REQUEST['confirmpassword']) && $_REQUEST['specifiedpassword'] == $_REQUEST['confirmpassword']
			? $_REQUEST['specifiedpassword']
			: '';

		$username = !empty($this->_user)
			? $this->_user->username
			: $user->username;
		if (!smfapi_getUserByUsername($username)) {
			$this->addUserToSMF($user->username);
		}
		elseif ($this->_user) {
			$current = array_merge($this->_user->toArray(), $this->_profile->toArray());
			$new = array_merge($user->toArray(), $profile->toArray());

			$update = array(
				'member_name' => 'username',
				'email_address' => 'email',
				'real_name' => 'fullname',
				'date_registered' => 'createdon',
				'birthdate' => 'dob',
				'website_url' => 'website',
				'location' => 'city',
				'gender' => 'gender',
			);
			foreach ($update as $k => $v) {
				if ($new[$v] != $current[$v]) {
					if ($k == 'birthdate') {
						$update[$k] = date('Y-m-d', $new[$v]);
					}
					else {
						$update[$k] = $new[$v];
					}
				}
				else {
					unset($update[$k]);
				}
			}

			if (!empty($password)) {
				$update['passwd'] = sha1(strtolower($user->username) . smfapi_unHtmlspecialchars($password));
			}
			if ($this->_user->active != $user->active || $this->_profile->blocked != $profile->blocked) {
				$update['is_activated'] = $user->active && !$profile->blocked
					? 1
					: 3;
			}

			if (!empty($update)) {
				$response = smfapi_updateMemberData($this->_user->username, $update);
				if (is_array($response)) {
					$this->modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not update user \"{$username}\" {$this->modx->event->name} in SMF: " . print_r($response, true));
				}
				elseif (!empty($update['passwd'])) {
					$contexts = $this->smfGetContexts();
					if (in_array($this->modx->context->key, $contexts) && $this->modx->user->username == $user->username) {
						smfapi_logout($this->_user->username);
						smfapi_login($user->username);
					}
				}
			}
		}
	}


	/**
	 * @param array $data
	 */
	public function OnUserChangePassword(array $data) {
		if (!defined('SMF') || SMF != 'API') {
			return;
		}

		if (!smfapi_getUserByUsername($data['user']->username)) {
			if (!$this->addUserToSMF($data['user']->username)) {
				return;
			}
		}

		smfapi_updateMemberData($data['user']->username, array(
			'password' => sha1(strtolower($data['user']->username) . smfapi_unHtmlspecialchars($data['newpassword'])),
		));

		$contexts = $this->smfGetContexts();
		if (in_array($this->modx->context->key, $contexts) && $this->modx->user->username == $data['user']->username) {
			smfapi_logout($data['user']->username);
			smfapi_login($data['user']->username);
			@session_write_close();
		}
	}


	/**
	 * @param array $data
	 */
	public function OnUserRemove(array $data) {
		if (!defined('SMF') || SMF != 'API') {
			return;
		}

		smfapi_deleteMembers($data['user']->username);
	}


	/**
	 * @param array $data
	 */
	public function OnWebLogin(array $data) {
		if (!defined('SMF') || SMF != 'API') {
			return;
		}

		if (!smfapi_getUserByUsername($data['user']->username)) {
			if (!$this->addUserToSMF($data['user']->username)) {
				return;
			}
		}
		smfapi_login($data['user']->username, $data['attributes']['lifetime']);

		@session_write_close();
	}


	/**
	 * @param array $data
	 */
	public function OnWebLogout(array $data) {
		if (!defined('SMF') || SMF != 'API') {
			return;
		}

		if (!smfapi_getUserByUsername($data['user']->username)) {
			if (!$this->addUserToSMF($data['user']->username)) {
				return;
			}
		}
		smfapi_logout($data['username']);

		@session_write_close();
	}


	/** SMF functions */


	/**
	 *
	 */
	protected function _loadSMF() {
		if (defined('SMF') && SMF == 'API') {
			return;
		}

		$smf_base = $this->modx->getOption('smf_path', $this->modx->config, '{base_path}forum/', true);
		$smf_base = str_replace('{base_path}', MODX_BASE_PATH, rtrim(trim($smf_base), '/')) . '/';
		$smf_base = preg_replace('#/+#', '/', $smf_base);
		if ($smf_base[0] != '/') {
			$smf_base = MODX_BASE_PATH . $smf_base;
		}

		$settings = $smf_base . 'Settings.php';
		$api = $this->config['controllersPath'] . 'smf-api.php';

		if (!file_exists($settings)) {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not load SMF settings at \"{$settings}\"");

			return;
		}
		else {
			file_put_contents($this->config['controllersPath'] . 'smfapi_settings.txt', base64_encode($settings));
			require_once $api;
		}

		// Add MODX controller to SMF
		$controller = $this->config['corePath'] . 'controllers/smf-include.php';

		/**@var array $modSettings */
		if (empty($modSettings['integrate_pre_include']) || $modSettings['integrate_pre_include'] != $controller) {
			if (!empty($modSettings['integrate_pre_include'])) {
				if (!function_exists('add_integration_function')) {
					require $smf_base . 'Sources/Subs.php';
					require $smf_base . 'Sources/Load.php';
				}
				$tmp = array_map('trim', explode(',', $modSettings['integrate_pre_include']));
				foreach ($tmp as $v) {
					remove_integration_function('integrate_pre_include', $v);
				}
				add_integration_function('integrate_pre_include', $controller);
				add_integration_function('integrate_login', 'MODX_SMF::smfOnUserLogin');
				add_integration_function('integrate_logout', 'MODX_SMF::smfOnUserLogout');
				add_integration_function('integrate_reset_pass', 'MODX_SMF::smfOnUserResetPass');
				add_integration_function('integrate_activate', 'MODX_SMF::smfOnUserActivate');
				add_integration_function('integrate_change_member_data', 'MODX_SMF::smfOnUserUpdate');
				add_integration_function('integrate_register', 'MODX_SMF::smfOnUserRegister');
				add_integration_function('integrate_delete_member', 'MODX_SMF::smfOnUserDelete');
			}
		}
	}


	/**
	 * @return array
	 */
	public function smfGetContexts() {
		$contexts = array();

		$c = $this->modx->newQuery('modContext', array('key:!=' => 'mgr'));
		$c->select('key');
		$c->sortby('rank', 'ASC');
		$setting = $this->modx->getOption('smf_user_contexts');
		if (!empty($setting)) {
			$setting = array_map('trim', explode(',', $setting));
			$c->where(array('key:IN' => $setting));
		}

		if ($c->prepare() && $c->stmt->execute()) {
			$contexts = $c->stmt->fetchAll(PDO::FETCH_COLUMN);
		}

		return $contexts;
	}


	/**
	 * @return array|null
	 */
	public function smfGetUserGroups() {
		$groups = array();
		if ($user_groups = $this->modx->getOption('smf_user_groups')) {
			$tmp = array_map('trim', explode(',', $user_groups));
			foreach ($tmp as $v) {
				if (strpos($v, ':') !== false) {
					list($group, $role) = array_map('trim', explode(':', $v));
				}
				else {
					$group = $v;
					$role = 1;
				}
				/** @var modUserGroup $tmp */
				if ($tmp = $this->modx->getObject('modUserGroup', array('id' => $group, 'OR:name:=' => $group))) {
					$groups[] = array(
						'usergroup' => $tmp->get('id'),
						'role' => $role,
					);
				}
			}
		}

		return !empty($groups)
			? $groups
			: null;
	}


	/**
	 * @param $username
	 * @param $password_hash
	 * @param $lifetime
	 */
	static function smfOnUserLogin($username, $password_hash, $lifetime) {
		global $modx, $MODX_SMF;

		$lifetime *= 60;

		/** @var modUser $user */
		if (!$user = $modx->getObject('modUser', array('username' => $username))) {
			$user = $MODX_SMF->addUserToMODX($username);
		}
		if ($user && $user->active && !$user->Profile->blocked) {
			$modx->user = $user;
			$contexts = $MODX_SMF->smfGetContexts();

			$modx->invokeEvent('OnWebAuthentication', array(
				'user' => $user,
				'password' => null,
				'rememberme' => $lifetime != 0,
				'lifetime' => $lifetime,
				'loginContext' => $contexts[0],
				'addContexts' => implode(',', array_slice($contexts, 1)),
			));

			foreach ($contexts as $context) {
				$modx->user->addSessionContext($context);
				$_SESSION['modx.' . $context . '.session.cookie.lifetime'] = $lifetime
					? $lifetime
					: 0;
			}

			$modx->invokeEvent("OnWebLogin", array(
				'user' => $user,
				'attributes' => array(
					'rememberme' => $lifetime != 0,
					'lifetime' => $lifetime,
					'loginContext' => $contexts[0],
					'addContexts' => implode(',', array_slice($contexts, 1)),
				),
			));
		}

		@session_write_close();
	}


	/**
	 *
	 */
	static function smfOnUserLogout() {
		global $modx, $MODX_SMF;

		$contexts = $MODX_SMF->smfGetContexts();
		$modx->invokeEvent('OnBeforeWebLogout', array(
			'userid' => $modx->user->id,
			'username' => $modx->user->username,
			'user' => $modx->user,
			'loginContext' => $contexts[0],
			'addContexts' => implode(',', array_slice($contexts, 1)),
		));

		foreach ($contexts as $context) {
			$modx->user->removeSessionContext($context);
		}

		$modx->invokeEvent('OnWebLogout', array(
			'userid' => $modx->user->get('id'),
			'username' => $modx->user->get('username'),
			'user' => $modx->user,
			'loginContext' => $contexts[0],
			'addContexts' => implode(',', array_slice($contexts, 1)),
		));

		@session_write_close();
	}


	/**
	 * Change user password
	 *
	 * @param $old_username
	 * @param $username
	 * @param $password
	 */
	static function smfOnUserResetPass($old_username, $username, $password) {
		global $modx, $MODX_SMF;

		/** @var modUser $user */
		if (!$user = $modx->getObject('modUser', array('username' => $username))) {
			$user = $MODX_SMF->addUserToMODX($username);
		}
		if ($user) {
			/** @var modProcessorResponse $response */
			$response = $MODX_SMF->runProcessor('security/user/update', array(
				'id' => $user->id,
				'password' => $password,
			));
			if ($response->isError()) {
				$modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not reset password for user \"{$username}\": " . print_r($response->getAllErrors(), true));
			}
		}
	}


	/**
	 * @param $options
	 */
	static function smfOnUserRegister($options) {
		global $modx, $MODX_SMF;

		$username = @$options['username'];
		$password = @$options['password'];
		$email = @$options['email'];
		$activated = !empty($options['require']) && $options['require'] == 'nothing';

		/**@var modProcessorResponse $response */
		if (!empty($username)) {
			$groups = $MODX_SMF->smfGetUserGroups();

			/** @var modUser $user */
			if ($user = $modx->getObject('modUser', array('username' => $username))) {
				if (!$modx->getOption('smf_forced_sync')) {
					// We can`t overwrite existing user
					return;
				}

				$response = $MODX_SMF->runProcessor('security/user/update', array(
					'id' => $user->id,
					'username' => $username,
					'password' => $password,
					'email' => $email,
					'groups' => $groups,
				));
			}
			else {
				$response = $MODX_SMF->runProcessor('security/user/create', array(
					'username' => $username,
					'fullname' => $username,
					'password' => $password,
					'email' => $email,
					'active' => $activated,
					'groups' => $groups,
				));
			}

			if ($response->isError()) {
				$modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not register user \"{$username}\": " . print_r($response->getAllErrors(), true));
			}
		}
	}


	/**
	 * @param $username
	 */
	static function smfOnUserActivate($username) {
		global $modx, $MODX_SMF;

		/** @var modUser $user */
		if (!$user = $modx->getObject('modUser', array('username' => $username))) {
			$user = $MODX_SMF->addUserToMODX($username);
		}
		if ($user && !$user->active) {
			$response = $MODX_SMF->runProcessor('security/user/activatemultiple', array(
				'users' => $user->id,
			));
			if ($response->isError()) {
				$modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not activate user \"{$username}\": " . print_r($response->getAllErrors(), true));
			}
		}
	}


	/**
	 * @param $usernames
	 * @param $key
	 * @param $value
	 */
	static function smfOnUserUpdate($usernames, $key, $value) {
		global $modx, $MODX_SMF;

		foreach ($usernames as $username) {
			/** @var modUser $user */
			if (!$user = $modx->getObject('modUser', array('username' => $username))) {
				$user = $MODX_SMF->addUserToMODX($username);
			}
			if ($user) {
				$data = array(
					'id' => $user->id,
					//'groups' => $MODX_SMF->smfGetUserGroups(),
				);
				// Convert values
				switch ($key) {
					case 'member_name':
						$data['username'] = $value;
						break;
					case 'real_name':
						$data['fullname'] = $value;
						break;
					case 'email_address':
						$data['email'] = $value;
						break;
					case 'gender':
						$data['gender'] = $value;
						break;
					case 'birthdate':
						$data['dob'] = $value;
						break;
					case 'website_url':
						$data['website'] = $value;
						break;
					case 'location':
						$data['city'] = $value;
						break;
					case 'avatar':
						if (!empty($value) && strpos($value, '://') !== false) {
							$data['photo'] = $value;
						}
						break;
				}
				$response = $MODX_SMF->runProcessor('security/user/update', $data);
				if ($response->isError()) {
					$modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not update user \"{$username}\": " . print_r($response->getAllErrors(), true));
				}
			}
		}
	}


	/**
	 * @param $uid
	 */
	static function smfOnUserDelete($uid) {
		global $modx, $MODX_SMF;

		if ($data = smfapi_getUserById($uid)) {
			$username = $data['member_name'];
			if ($user = $modx->getObject('modUser', array('username' => $username))) {
				$response = $MODX_SMF->runProcessor('security/user/delete', array(
					'id' => $user->id,
				));
				if ($response->isError()) {
					$modx->log(modX::LOG_LEVEL_ERROR, "[SMF] Could not delete user \"{$username}\": " . print_r($response->getAllErrors(), true));
				}
			}
		}
	}

}