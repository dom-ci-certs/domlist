<?php
class domlist_server {
	
	/**
	 * DB tabellen
	 */
	var $db_tbl_list      = 'domlist_list';		// listen
	var $db_tbl_user      = 'domlist_mem';		// benutzer
	var $db_tbl_user_list = 'domlist_memlist';	// zuordnung user->liste, plus statsu
	
	
	var $util = null;		// ice-util object
	
	var $params = array();	// beim request uebergebene parameter
	
	var $response_status_field = 'markus';
	
	function domlist_server() {
		$this->util = ICE::create('util');
	}
	
	
	/**
	 * dispatch requests
	 * @return 
	 */
	public function dispatch() {
		$this->params = array();
		
		//-- REST request
		if (isset($_REQUEST['rawaction'])) {
			$this->params = $_REQUEST;
			$action = strtolower($_REQUEST['rawaction']);
			
			if (method_exists($this, 'rawaction_' . $action)) {
				$this->{'rawaction_' . $action}();
			}
			else {
				$this->_return_error('unknown rawaction');
			}
			exit;
		}
		
	}
	
	
	/**
	 * alle vorhandenen listen liefern
	 * @return 
	 */
	public function rawaction_lists() {
		
		$tbl_fields = array('id', 'title', 'short', 'mobile', 'email');
		$sql_fields = implode(', ', $tbl_fields);
		
		//-- wenn ein (gueltiger) user uebergeben wurde, filtern wir die listen nach diesem user
		if (is_int($uid = $this->auth_user())) {
			//-- alle listen des user auslesen
			$usr_lists = $this->_q("SELECT lid FROM {$this->db_tbl_user_list} WHERE mid=$uid");
			$_in_lists = array();
			while($usr_lists->next()) $_in_lists[] = $usr_lists->f('lid');
			
			$res_lists = $this->_q("SELECT $sql_fields FROM {$this->db_tbl_list} WHERE id IN (" . implode(',', $_in_lists) . ") ORDER BY title ASC");
		}
		else {
			$res_lists = $this->_q("SELECT $sql_fields FROM {$this->db_tbl_list} AS l ORDER BY title ASC");
		}
		
		$lists = array();
		while($res_lists->next()) {
			$_list = array();
			foreach($tbl_fields as $field) {
				$_list[$field] = $this->util->db2plain($res_lists->f($field));
			}
			$lists[] = $_list;
		}
		
		$response = array(
			'response' => array(
				$this->response_status_field => 1,
				'lists' => array(
				)
			)
		);

		if (count($lists) > 0) {
			$response['response']['lists']['list'] = $lists;
		}
		
		$this->_return_response($response);
		
	}
	
	
	/**
	 * infos, mitglieder und deren status einer liste liefern
	 * @return 
	 */
	public function rawaction_users() {
		//-- listen-id muss uebergeben werden
		$lid = (int)$this->params['list'];
		
		$list = $this->_q("SELECT * FROM {$this->db_tbl_list} WHERE id=$lid");
		if ($list->nr() != 1) {
			$this->_return_error('unknown list: ' . $lid);
			return;
		}
		
		$res_user = $this->_q("SELECT u.name, u.mobile, u.id, ul.state, ul.ts ".
						" FROM {$this->db_tbl_user} AS u, {$this->db_tbl_user_list} AS ul ".
						" WHERE u.id=ul.mid AND ul.lid=$lid ORDER BY name");
		$users = array();
		while($res_user->next()) {
			$users[] = array(
				'name'    => utf8_encode($this->util->db2plain($res_user->f('name'))),
				'id'      => (int)$res_user->f('id'),
				'mobile'  => utf8_encode($this->util->db2plain($res_user->f('mobile'))),
				'status'  => (int)$res_user->f('state'),
				'changed' => date('d.m.Y H:i:s', $res_user->f('ts')),
			);
		}
		
		$response = array(
			'response' => array(
				$this->response_status_field => 1,
				'list' => array(
					'users' => array(
					),
				)
			)
		);
		
		if (count($users) > 0) {
			$response['response']['list']['users']['user'] = $users;
		}
		
		$this->_return_response($response);
	}
	
	
	/**
	 * status eines users setzen
	 * @return 
	 */
	public function rawaction_setstatus() {
		//-- wir brauchen die listen-id
		$lid = (int)$this->params['list'];
		
		$list = $this->_q("SELECT * FROM {$this->db_tbl_list} WHERE id=$lid");
		if ($list->nr() != 1) {
			$this->_return_error('unknown list: ' . $lid);
			return;
		}
		
		//-- wir brauchen eine status-nr (0-2)
		if (!isset($this->params['status'])) {
			$this->_return_error('missing status');
			return;
		}
		$status = (int)$this->params['status'];
		if ($status < 0 || $status > 3) {
			$this->_return_error('invalid status');
			return;
		}
		
		//-- der user muss sich identifizieren
		if (!is_int($uid = $this->auth_user())) {
			$this->_return_error('unknown user: ' . $uid);
			return;			
		}
		
		//-- der user muss in dieser liste sein
		$check = $this->_q("SELECT count(*) AS cnt FROM {$this->db_tbl_user_list} WHERE lid=$lid AND mid=$uid");
		if ($check->f('cnt') != 1) {
			$this->_return_error('user not in list');
			return;
		}
		
		//-- alles ok, feld setzen
		$now = time();
		$this->_q("UPDATE {$this->db_tbl_user_list} SET state=$status, ts=$now WHERE mid=$uid AND lid=$lid LIMIT 1");
		
		$response = array(
			'response' => array(
				$this->response_status_field => 1,
			),
		);
		$this->_return_response($response);
	}
	
	
	/**
	 * user autentifizieren. z.zt. ueber handy-nr oder mail-adr
	 * @return 
	 */
	public function auth_user() {
		if (!isset($this->params['useridfield'])) return 'useridfield missing';
		if (!isset($this->params['userid'])) return 'userid missing';
		
		$sql_field = '';
		if ($this->params['useridfield'] == 'email') {
			$sql_field = 'email';
		}
		elseif ($this->params['useridfield'] == 'mobile') {
			$sql_field = 'mobile';
		}
		else {
			return 'invalid useridfield';
		}
		
		$sql_where = "$sql_field='" . $this->util->plain2db($this->params['userid']) . "'";
		
		$check = $this->_q("SELECT id FROM {$this->db_tbl_user} WHERE $sql_where");
		
		return ($check->nr() == 1) ? (int)$check->f('id') : 'user not found'; 
	}
	
	
	/**
	 * fehlermeldung zurueckgeben
	 * @return 
	 * @param object $msg
	 */
	private function _return_error($msg) {
		$repsonse = array(
			'response' => array(
				$this->response_status_field => 0,
				'error' => $msg,
			)
		);
		
		echo $this->_return_response($repsonse);
	}
	
	
	/**
	 * response array abhaengig vom angefragten format zurueckgeben
	 * @return 
	 * @param object $response
	 */
	private function _return_response($response) {
		if (!is_array($response)) return false;
		
		switch($this->_get_response_format()) {
			case 'xml':
				include_once(dirname(__FILE__) . '/class.xml_construct.php');
				$dom = new XmlDomConstruct('1.0', 'utf-8');
				$dom->fromMixed($response);
				$out = $dom->saveXML();
				
				header('Content-Type: text/xml');
				echo $out;
				
				break;
				
			case 'json':
				include_once(dirname(__FILE__) . '/class.json.php');
				$json = new Services_JSON();
				$out  = $json->encode($response);
				
				header('Content-type: application/json');
				echo $out;
				
				break;
				
			case 'yaml':
				include_once(dirname(__FILE__) . '/class.spyc.php');
				$out = Spyc::YAMLDump($response);
				
				//header('Content-type: text/yaml');
				echo $out;

				break;
			default:
				echo 'error: unknown response format';
		}
	}
	

	
	/**
	 * format der ausgabe bestimmen (xml, json, ...)
	 * @return 
	 */
	private function _get_response_format() {
		$fmt = strtolower($this->params['format']);
		
		static $supported = array('xml', 'json', 'yaml');
		
		return in_array($fmt, $supported) ? $fmt : 'xml';
	}
	
	
	/**
	 * sql query ausfuehren
	 * @return object
	 * @param string $sql
	 */
	private function _q($sql) {
		return ICE::query($sql);
	}
	
}

