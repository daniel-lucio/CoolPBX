<?php

/**
 * plugin_totp
 *
 * @method openid time based one time password authenticate the user
 */
class plugin_openid {

	/**
	 * Define variables and their scope
	 */
	public $debug;
	public $domain_name;
	public $username;
	public $password;
	public $user_uuid;
	public $user_email;
	public $contact_uuid;

	/**
	 * time based one time password aka totp
	 * @return array [authorized] => true or false
	 */
	function openid() {
openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
syslog(LOG_WARNING, 'openid()');
closelog();
		//pre-process some settings
			$settings['theme']['favicon'] = !empty($_SESSION['theme']['favicon']['text']) ? $_SESSION['theme']['favicon']['text'] : PROJECT_PATH.'/themes/default/favicon.ico';
			$settings['login']['destination'] = !empty($_SESSION['login']['destination']['text']) ? $_SESSION['login']['destination']['text'] : '';
			$settings['users']['unique'] = !empty($_SESSION['users']['unique']['text']) ? $_SESSION['users']['unique']['text'] : '';
			$settings['theme']['logo'] = !empty($_SESSION['theme']['logo']['text']) ? $_SESSION['theme']['logo']['text'] : PROJECT_PATH.'/themes/default/images/logo_login.png';
			$settings['theme']['login_logo_width'] = !empty($_SESSION['theme']['login_logo_width']['text']) ? $_SESSION['theme']['login_logo_width']['text'] : 'auto; max-width: 300px';
			$settings['theme']['login_logo_height'] = !empty($_SESSION['theme']['login_logo_height']['text']) ? $_SESSION['theme']['login_logo_height']['text'] : 'auto; max-height: 300px';
			$settings['login']['destination'] = !empty($_SESSION['login']['destination']['text']) ? $_SESSION['login']['destination']['text'] : '/core/dashboard/';

			if (substr($settings['login']['destination'], 0, 4) != 'http'){
				$settings['login']['destination'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$settings['login']['destination'];
			}
			
		//get the username
			if (isset($_SESSION["username"])) {
				$this->username = $_SESSION["username"];
			}
			if (isset($_POST['username'])) {
				$this->username = $_POST['username'];
			}

			$ch = curl_init($_SESSION['openid']['metadata_url']['text']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$metadata = json_decode(curl_exec($ch));
			curl_close($ch);
			$client_id = $_SESSION['openid']['client_id']['text'];
			$secret_id = $_SESSION['openid']['secret_id']['text'];

		//request the username
			if(!isset($_GET['code'])) {

				//set a default template
				$_SESSION['domain']['template']['name'] = 'default';
				$_SESSION['theme']['menu_brand_image']['text'] = PROJECT_PATH.'/themes/default/images/logo.png';
				$_SESSION['theme']['menu_brand_type']['text'] = 'image';

				//get the domain
				$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
				$domain_name = $domain_array[0];

				//temp directory
				$_SESSION['server']['temp']['dir'] = '/tmp';

				//create token
				//$object = new token;
				//$token = $object->create('login');

				//add multi-lingual support
				$language = new text;
				$text = $language->get(null, '/core/authentication');

				//initialize a template object
				$view = new template();
				$view->engine = 'smarty';
				$view->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/core/authentication/resources/views/';
				$view->cache_dir = $_SESSION['server']['temp']['dir'];
				$view->init();

				//assign default values to the template
				$view->assign("project_path", PROJECT_PATH);
				$view->assign("login_destination_url", $settings['login']['destination']);
				$view->assign("favicon", $settings['theme']['favicon']);
				$view->assign("login_title", $text['label-username']);
				$view->assign("login_username", $text['label-username']);
				$view->assign("login_logo_width", $settings['theme']['login_logo_width']);
				$view->assign("login_logo_height", $settings['theme']['login_logo_height']);
				$view->assign("login_logo_source", $settings['theme']['logo']);
				$view->assign("button_login", $text['button-login']);
				$view->assign("favicon", $settings['theme']['favicon']);
				$view->assign("button_oid", $text['button-oid']);
				
				$_SESSION['state'] = bin2hex(random_bytes(5));
				$_SESSION['code_verifier'] = bin2hex(random_bytes(50));
				$code_challenge = $this->base64_urlencode(hash('sha256', $_SESSION['code_verifier'], true));
				$authorize_url = $metadata->authorization_endpoint.'?'.http_build_query([
					'response_type' => 'code',
					'client_id' => $client_id,
					'redirect_uri' => $settings['login']['destination'],
					'state' => $_SESSION['state'],
					'scope' => 'openid profile email',
					'code_challenge' => $code_challenge,
					'code_challenge_method' => 'S256',
				]);
				$view->assign("a_oid", $authorize_url);
				
				//show the views
				$content = $view->render('openid.htm');
				echo $content;
				exit;
			}

		//if authorized then verify
			else {
openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
syslog(LOG_WARNING, 'openid() GOT A CODE');
closelog();
			
				if($_SESSION['state'] != $_GET['state']) {
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, "Authorization server returned an invalid state parameter");
					closelog();
					die('Authorization server returned an invalid state parameter');
				}
	
				if(isset($_GET['error'])) {
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, 'Authorization server returned an error: '.htmlspecialchars($_GET['error']));
					closelog();
					die('Authorization server returned an error: '.htmlspecialchars($_GET['error']));
				}
				unset($params);
				$params = [
					'grant_type' => 'authorization_code',
					'code' => $_GET['code'],
					'redirect_uri' => $settings['login']['destination'],
					'client_id' => $client_id,
					'client_secret' => $secret_id,
					'code_verifier' => $_SESSION['code_verifier'],
				];
	
				$ch = curl_init($metadata->token_endpoint);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				$response = json_decode(curl_exec($ch));
				curl_close($ch);
				$access_token = $response->access_token;
	
				if(!isset($response->access_token)) {
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, 'Error fetching access token');
					syslog(LOG_WARNING, "metadata->token_endpoint: ".$metadata->token_endpoint);
					syslog(LOG_WARNING, "params: ".print_r($params, true));
					syslog(LOG_WARNING, "response: ".print_r($response, true));
					closelog();
					die('Error fetching access token'.PHP_EOL."metadata->token_endpoint: ".$metadata->token_endpoint.PHP_EOL."params: ".print_r($params, true));
				}
	
				$params = [
					'access_token' => $access_token,
				];
				$ch = curl_init($metadata->userinfo_endpoint);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				$userinfo = json_decode(curl_exec($ch));
				curl_close($ch);
					
				if(!isset($userinfo->sub)) {
					openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
					syslog(LOG_WARNING, 'No userinfo returned');
					closelog();
					die('No userinfo returned');
				}
				$auth_valid = true;
				$this->username = $userinfo->profile->preferred_username;
	
				// Create the user
	
				$sql = "select * from v_users ";
				$sql .= "where username=:username ";
				
				if ($_SESSION["user"]["unique"]["text"] == "global") {
						//unique username - global (example: email address)
				}
				else {
						//unique username - per domain
						$sql .= "and domain_uuid=:domain_uuid ";
						$parameters['domain_uuid'] = $this->domain_uuid;
				}
				$parameters['username'] = $this->username;
				$database = new database;
				#row = $database->select($sql, $parameters, 'row');
				
				if (is_array($row)) {
					if ($_SESSION["user"]["unique"]["text"] == "global" && $row["domain_uuid"] != $this->domain_uuid) {
						//get the domain uuid
							$this->domain_uuid = $row["domain_uuid"];
							$this->domain_name = $_SESSION['domains'][$this->domain_uuid]['domain_name'];

						//set the domain session variables
							$_SESSION["domain_uuid"] = $this->domain_uuid;
							$_SESSION["domain_name"] = $this->domain_name;

						//set the setting arrays
							$domain = new domains();
							$domain->db = $db;
							$domain->set();
					}
					$this->user_uuid = $row["user_uuid"];
					$this->contact_uuid = $row["contact_uuid"];
				}
				else {
					//salt used with the password to create a one way hash
						$salt = generate_password('32', '4');
						$password = generate_password('32', '4');
	
					//prepare the uuids
						$this->user_uuid = uuid();
						$this->contact_uuid = uuid();
						$this->username = $userinfo->preferred_username;
					//build user insert array
						$array['users'][0]['user_uuid'] = $this->user_uuid;
						$array['users'][0]['domain_uuid'] = $this->domain_uuid;
						$array['users'][0]['contact_uuid'] = $this->contact_uuid;
						$array['users'][0]['username'] = strtolower($this->username);
						$array['users'][0]['password'] = md5($salt.$password);
						$array['users'][0]['salt'] = $salt;
						$array['users'][0]['add_date'] = now();
						$array['users'][0]['add_user'] = strtolower($this->username);
						$array['users'][0]['user_enabled'] = 'true';

					//build user group insert array
						$array['user_groups'][0]['user_group_uuid'] = uuid();
						$array['user_groups'][0]['domain_uuid'] = $this->domain_uuid;
						$array['user_groups'][0]['group_name'] = 'user';
						$array['user_groups'][0]['user_uuid'] = $this->user_uuid;

					//grant temporary permissions
						$p = new permissions;
						$p->add('user_add', 'temp');
						$p->add('user_group_add', 'temp');

					//execute insert
						$database = new database;
						$database->app_name = 'authentication';
						$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932be0e41f1';
						$database->save($array);
						unset($array);

					//revoke temporary permissions
						$p->delete('user_add', 'temp');
						$p->delete('user_group_add', 'temp');	
				}
		
				
				//build the result array
				$result["plugin"] = "openid";
				$result["domain_name"] = $_SESSION["domain_name"];
				$result["username"] = $_SESSION["username"];
				$result["user_uuid"] = $_SESSION["user_uuid"];
				$result["domain_uuid"] = $_SESSION["domain_uuid"];
				$result["contact_uuid"] = $_SESSION["contact_uuid"];
				$result["authorized"] = $auth_valid ? true : false;
openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
syslog(LOG_WARNING, 'openid()'.print_r($result, true));
closelog();

			//add the failed login to user logs
				if (!$auth_valid) {
					user_logs::add($result);
				}

			//retun the array
				return $result;

			//$_SESSION['authentication']['plugin']['totp']['plugin'] = "totp";
			//$_SESSION['authentication']['plugin']['totp']['domain_name'] = $_SESSION["domain_name"];
			//$_SESSION['authentication']['plugin']['totp']['username'] = $row['username'];
			//$_SESSION['authentication']['plugin']['totp']['user_uuid'] = $_SESSION["user_uuid"];
			//$_SESSION['authentication']['plugin']['totp']['contact_uuid'] = $_SESSION["contact_uuid"];
			//$_SESSION['authentication']['plugin']['totp']['domain_uuid'] =  $_SESSION["domain_uuid"];
			//$_SESSION['authentication']['plugin']['totp']['authorized'] = $auth_valid ? true : false;

		}
	}

	private function base64_urlencode($string) {
  		return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
	}

}
