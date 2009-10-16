<?

/**
* main ice-class(es)
*
*
* rocker baby
*
* @copyright	2001, 2002 INTERCOASTER oHG
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @version	2.07 (LISA), 25.07.2002
* @history	25.07.02, jkl, added 2nd argument in fctn. gateway(), to switch between admin/online gateway
* @histoty	15.08.02, jkl, added fctn. login_ok()
* @history	20.08.02, jkl, changed fctn. app() to not change 'last_app' when app is 'ice'
* @history	26.11.02, jkl, added fctn. sess_start()
* @history	04.09.03, jkl, added fctn. db_set(), db_get(), db_tables(); added constants for system-check
* @history	16.03.05, jkl, added params 'exclude' to link_hidden()
* @access	public
* @package	ICE
*/





/**
* consts
*/

define('ICE_CAT_DEFAULT_ID', 99999);	// id f�r default-category (lost&found)
//_TODO will break a neck some peacefull day


define('ICE_ERR_MESSAGE', '90');
define('ICE_ERR_WARNING', '91');

define('ICE_FTYPE_TEXT',      200);
define('ICE_FTYPE_TEXTFIELD', 201);
define('ICE_FTYPE_EMAIL',     202);
define('ICE_FTYPE_DATE',      203);
define('ICE_FTYPE_TIME',      204);
define('ICE_FTYPE_DATE_TIME', 205);
define('ICE_FTYPE_PHONE',     206);
define('ICE_FTYPE_NUMBER',    207);
define('ICE_FTYPE_USR',       208);
define('ICE_FTYPE_PWD',       209);
define('ICE_FTYPE_ON_OFF',    210);
define('ICE_FTYPE_ID',        211);
define('ICE_FTYPE_DIGIT',     212);

define('ICE_FTYPE_ERR_EMPTY',  300);	// feld war leer
define('ICE_FTYPE_ERR_FORMAT', 301);	// feld hatte falsches format

define('ICE_GUI_MSG_ERROR',    100);
define('ICE_GUI_MSG_OK',       101);
define('ICE_GUI_MSG_QUESTION', 102);
define('ICE_GUI_MSG_PLAIN',    103);

//-- klassen
define('ICE_CLASS_USR',            'ice/icIceUser');
define('ICE_CLASS_VALIDATE',       'ice/icIceValidate');
define('ICE_CLASS_LOGIN',          'ice/icIceLogin');
define('ICE_CLASS_TEMPLATE',       'html/icTemplate');
define('ICE_CLASS_GUI',            'html/icGui');
define('ICE_CLASS_TREE_MENU',      'html/icTreeMenu');
define('ICE_CLASS_PARENT_TREE',    'db/icParentTree');
define('ICE_CLASS_UTIL', 		   'ice/icIceUtil');
define('ICE_CLASS_HTML_TABLE',     'html/icHtmlTable');
define('ICE_CLASS_DOB',            'ice/icDataObject');
define('ICE_CLASS_CURRENCY',       'shop/icObjCurrency');
define('ICE_CLASS_SHOPPING_CART',  'shop/icShoppingCart');
define('ICE_CLASS_PARSER',         'parser/icParser');
define('ICE_CLASS_CMS_FILE',       'cms/icCmsFile');
define('ICE_CLASS_CMS_TAG_PARSER', 'cms/icCmsTagParser');
define('ICE_CLASS_DB_GRAPH',       'db/icDbGraph');
define('ICE_CLASS_DB_SEARCH',      'db/icDbSearch');
define('ICE_CLASS_CSV',            'files/icCsv');
define('ICE_CLASS_PCS_PARSER',     'pcs/icPcsParser');
define('ICE_CLASS_COMPILER',       'pcs/icCompiler');
define('ICE_CLASS_TABS',           'html/icTabs');
define('ICE_CLASS_ACL',            'ice/icIceAcl');

//-- fuer stati der system-checks
define('ICE_SYSTEM_CHECK_OK',      1);
define('ICE_SYSTEM_CHECK_ERROR',   2);
define('ICE_SYSTEM_CHECK_FIXED',   3);
define('ICE_SYSTEM_CHECK_WARNING', 4);
define('ICE_SYSTEM_CHECK_FATAL',   5);
define('ICE_SYSTEM_CHECK_INFO',    6);

$_myice = new icIce;	// tadaa. up and running :) have a nice day
// this is the static class
class ICE
{

	function ICE() { }
	
	function addlog()
	{
		//_TODO nearly nobody uses this ... :(
		$logfile = ICE::get('ice', 'path', 'files').'ic/ic_log.dat';
		
		if (($fp = @fopen($logfile, 'a')))
		{
			$args = func_get_args();
			$name = array_shift($args);
			
			$args = str_replace("\t", '\t', implode('||', $args));
			
			$time     = microtime();
			$sess_id  = ICE::get('session', 'id');
			$app      = ICE::get('ice', 'app', 'name');
			$usr_id   = ICE::get('usr', 'id');
			$usr_name = ICE::get('usr', 'usr');
			$usr_full = ICE::get('usr', 'fullname');
			$ip       = $GLOBALS["REMOTE_ADDR"];
			$browser  = $GLOBALS["HTTP_USER_AGENT"];
			
			$s = "\t";
			$line = $app.$s.$name.$s.$args.$s.$usr_id.$s.$usr_name.$s.$usr_full.$s.$time.$s.$sess_id.$s.$ip.$s.$browser."\n";
			
			@fwrite($fp, $line);
			@fclose($fp);
		}
	}
	
	
	//_TODO we have to speed this up !
	function set()
	{
		global $_myice;
		$args = func_get_args();

		switch(count($args)) {
		case 3:
			$_myice->info[$args[0]][$args[1]] = $args[2];
			break;
		case 4:
			$_myice->info[$args[0]][$args[1]][$args[2]] = $args[3];
			break;
		case 5:
			$_myice->info[$args[0]][$args[1]][$args[2]][$args[3]] = $args[4];
			break;			
		default:
			echo "??? unknown set";
			die;
		}
	}


	//_TODO this too
	function get()
	{
		global $_myice;
//		$_myice->abc++;
//		ICE::vd($_myice->abc);
		$args = func_get_args();
		switch(count($args)) {
			case 2:
				return @$_myice->info[$args[0]][$args[1]];
				break;
			case 3:
				return @$_myice->info[$args[0]][$args[1]][$args[2]];
				break;
			case 4:
				return @$_myice->info[$args[0]][$args[1]][$args[2]][$args[3]];
				break;
			default:
				echo "??? unknown get"; ICE::vd($args); die;
		}
	}

	
	function query($sql, $debug = FALSE, $ignore_err = FALSE, $result_start = 0, $result_limit = 0)
	{
		global $_myice;
		return $_myice->class_db->query($sql, $debug, $ignore_err, $result_start, $result_limit);
	}
	
	// setzt variablen in der db-klasse
	function db_set($var, $val) {
		global $_myice;
		return $_myice->class_db->set($var, $val);
	}
	// liest variablen aus der db-klasse
	function db_get($var) {
		global $_myice;
		return $_myice->class_db->get($var);
	}
	
	// liefert alle tabellen der aktuellen db
	function db_tables() {
		global $_myice;
		return $_myice->class_db->get_tables($var, $val);
	}

	function sess_start($name = 'icesess', $cookies = FALSE)
	{
		global $_myice;
		if (is_object($_myice->class_sess)) return TRUE;
		
		if ($cookies == FALSE) @ini_set('session.use_cookies', 0);
		
		session_cache_limiter('private, must-revalidate');	// verhindert 'missing data' bei POST's
		include_once(ICE::get('ice', 'dir', 'ice') . 'ice/icIceSession.inc.php');
		$_myice->class_sess = new icIceSession($name);
	}
	
	// session functions
	function sess_set($key, $val)
	{
		global $_myice;
		$_myice->class_sess->set($key, $val);
	}
	// set persistent (fine)
	function sess_setp($key, $val)
	{
		global $_myice;
		$_myice->class_sess->setp($key, $val);
	}
	function sess_persistent_get()
	{
		global $_myice;
		return $_myice->class_sess->persistent_get();
	}
	function sess_persistent_set($per)
	{
		global $_myice;
		return $_myice->class_sess->persistent_set($per);
	}
	function sess_get($key = '')
	{
		global $_myice;
		return $_myice->class_sess->get($key);
	}
	function sess_unset($key)
	{
		global $_myice;
		$_myice->class_sess->uset($key);
	}
	function sess_isset($key)
	{
		global $_myice;
		return $_myice->class_sess->iset($key);
	}
	
	// translation
	function lang($msg_id, $vars = "")
	{
		global $_myice;
		if (!empty($vars) && !is_array($vars))			
		{
			$vars = func_get_args();
			array_shift($vars);
		}
		return $_myice->class_translate->translate($msg_id, $vars);
	}
	function plang($msg_id, $vars = "")
	{
		echo ICE::lang($msg_id, $vars);
	}
	
	function lang_app($app, $msg_id, $vars = "")
	{
		/*
		$last_app = ICE::get('app', 'name');
		ICE::set('app', 'name', $app);
		$ret = ICE::lang($msg_id, $vars);
		ICE::set('app', 'name', $last_app);
		*/
		ICE::app_push($app);
		$ret = ICE::lang($msg_id, $vars);
		ICE::app_pop();
		return $ret;
	}
	function plang_app($app, $msg_id, $vars = "")
	{
		echo ICE::lang_app($app, $msg_id, $vars);
	}
	
	// links
	function link($url = '')
	{
		global $_myice;
		
//		if (!ICE::get('session', 'id'))
		if (empty($url)) $url = $GLOBALS['PHP_SELF'];

		$url_pre = '';
		if (($url != '') && (preg_match('/\?/', $url))) {
			$url_pre = '&';
		}
		else {
			$url_pre = '?';
		}
		if (ICE::get('session', 'id')) {
			//$_myice->xyz++;
			//_TODO session id/name cachen ?! ????
			// session nur hinzufuegen, wenn noch nicht drin
			if (strpos($url, ICE::get('session', 'name')) === false) { 
				$url .= $url_pre . ICE::get('session', 'name').'='.ICE::get('session', 'id');
			}
		}
		else {
			$url .= $url_pre . 'icoaster=wrsi01';	// we dont have sessions online (well, mostly)
		}
		
		foreach($_myice->link_values_enc as $key => $val) {
			$url .= '&'. $key .'='. $val;
		}
		
		return $url;
	}
	
	// nur session-infos in link
	function link_sess($url = '')
	{
		if (empty($url)) $url = $GLOBALS['PHP_SELF'];


		if (($url != '') && (preg_match('/\?/', $url))) {
			$url .= '&';
		}
		else {
			$url .= '?';
		}
		if (ICE::get('session', 'id')) {
			//_TODO session id/name cachen ?!
			$url .= ICE::get('session', 'name').'='.ICE::get('session', 'id');
		}
		else {
			$url .= 'icoaster=wrsi01';
		}
		return $url;
	}
	
	function link_add($key, $val = "")
	{
		global $_myice;
		if (is_array($key)) {
			foreach($key as $k => $v) {
				$_myice->link_values[$k] = $v;
				$_myice->link_values_enc[urlencode($k)] = urlencode($v);
			}
		}
		else
		{
			$_myice->link_values[$key] = $val;
			$_myice->link_values_enc[urlencode($key)] = urlencode($val);
		}
	}
	function link_add_array($name, $array)
	{
		global $_myice;
		
		foreach($array as $k => $v) {
			$_myice->link_values[$name.'['.$k.']'] = $v;
			$_myice->link_values_enc[urlencode($name.'['.$k.']')] = urlencode($v);
		}

	}
	
	function link_unset($key)
	{
		global $_myice;
		unset($_myice->link_values[$key]);
		unset($_myice->link_values_enc[urlencode($key)]);
	}
	
	function link_get($key)
	{
		global $_myice;
		return $_myice->link_values[$key];
	}
	
	// versteckte felder zur�ckgeben
	function link_hidden($exclude = null)
	{
		global $_myice;
		
		$util = ICE::create('util');
		
		$hidden = '<input type="HIDDEN" name="%s" value="%s">';
		
		$ret = sprintf($hidden, ICE::get('session', 'name'), ICE::get('session', 'id'));
		
		foreach($_myice->link_values as $key => $val) {
			if (is_array($exclude) && in_array($key, $exclude)) continue;
			$ret .= sprintf($hidden, $util->plain2form($key), $util->plain2form($val));
		}
		
		return $ret;
	}
	
	
	// link auf index einer bestimmten app
	function link_app($app, $lnk_add = '')
	{
		return ICE::link_sess(ICE::get('ice', 'url', 'admin').$app.'/index.php?'.$lnk_add);
	}

	// zusatzklasse/object laden
	function create($name)
	{
//		$class_name = preg_replace('/^([a-z]+)\..+$/i', '\\1', basename(ICE::get('ice', 'class', $name)));
		list($package, $class_name) = explode('/', constant('ICE_CLASS_'.strtoupper($name)));
		
		if ($package == 'ice') $package = '';
		include_once(dirname(__FILE__).'/'.$package.'/'.$class_name.'.inc.php');
		
		//_TODO check, whether class-file exists <-> speed (?)
		if (func_num_args() == 1) {
			return (new $class_name);
		}
		elseif (func_num_args() == 2) {
			return (new $class_name(func_get_arg(1)));
		}
		elseif (func_num_args() == 3) {
			$a = func_get_arg(1);
			$b = func_get_arg(2);
			return (new $class_name($a, $b));
		}
	}
	
	/**
	* gateway-klasse einer applikation instanzieren
	*
	* @param	string	$name	name der applikation
	* @param	string	$switch	online oder admin gateway
	* @return object	gateway-objekt
	* @access	public
	*/
	function gateway($name, $switch = 'admin')
	{
		ICE::conf($name);
		$inc_path   = ICE::get('ice', 'dir', 'app').$name.'/'.ICE::get('ice', 'apps', $name, 'version').'/'.$switch.'/inc/';
		$class_name = 'gateway_'.$name.($switch == 'online' ? '_online' : '');
		include_once($inc_path.$class_name.'.inc.php');
		return (new $class_name);
	}
	

	/**
	* konfigurations-datei eines programms einbinden
	*/
	function conf($name)
	{
		include_once(ICE::get('ice', 'dir', 'cnf').$name.'/cnf.inc.php');
	}
	
	/**
	* dob-objekt instanzieren
	*
	* @param	string name der applikation
	* @param	string	name/typ des dobs
	* @return	object	dob-objekt
	* @access	public
	*/
	function dob($app_name, $dob_name)
	{
		$app_path = ICE::get('ice', 'apps', $app_name, 'path').'inc/';
		ICE::vd($app_path,'ICE dob');
		$dob = ICE::create('dob');
		
		include($app_path.'cnf_dob_'.$dob_name.'.inc.php');
		
		$dob->init($dob_info);	// dob_info kommt aus cnf-datei
		
		return $dob;
	}
	
	function error()
	{
	}
	

	
	
	function debug()
	{
		global $_myice;
		$_myice->debug();
	}
	
	// aktuelle application setzen
	function app($name)
	{
		global $_myice;
		ICE::set('ice', 'app', 'name', $name);
		ICE::set('ice', 'app', 'file_path', ICE::get('ice', 'path', 'files').$name.'/');
		$_myice->app_stack = array($name);
		
		// wir merken uns die letzte app (ausser ic selber)
		if($name != 'ic' && $name != 'ice' && is_object($_myice->class_sess)) {
			ICE::app_push('ic');
			ICE::sess_set('last_app', $name);
			ICE::app_pop();
		}
	}
	
	// app auf stack schieben und aktivieren
	// die unterste ist immer die ur-app (durch app() gesetzt)
	function app_push($name)
	{
		global $_myice;
		ICE::set('ice', 'app', 'name', $name);
		array_push($_myice->app_stack, $name);
	}
	
	// app von stack entfernen und vorige aktivieren
	function app_pop()
	{
		global $_myice;

		$app = array_pop($_myice->app_stack);
		// ist der stack jetzt leer, war das grade die ur-app,
		// die wir auch wieder draufschmeissen. stack wird also nie leer
		if (count($_myice->app_stack) == 0) {
			ICE::app($app);
		}
		else {
			// sonst nehme vorige
			$app = $_myice->app_stack[count($_myice->app_stack)-1];
			ICE::set('ice', 'app', 'name', $app);
		}
		
		return TRUE;
	}
	
	
	// variablen aus post oder get lesen
	function post_get_var($var)
	{
		global $HTTP_POST_VARS, $HTTP_GET_VARS;
		
		// nach var in post, dann get suchen
		if (isset($HTTP_POST_VARS[$var])) {
			return ($HTTP_POST_VARS[$var]);
		}
		elseif (isset($HTTP_GET_VARS[$var])) {
			return ($HTTP_GET_VARS[$var]);
		}
		// nix gefunden, dann auch submit bilder suchen (var_value_x)
		else {
			$vlen = strlen($var);

			foreach($HTTP_POST_VARS as $key => $val) {
				if (substr($key, 0, $vlen) == $var) {
					if (preg_match('/'.$var.'_(.+)_x/', $key, $match)) return $match[1];
				}
			}
			foreach($HTTP_GET_VARS as $key => $val) {
				if (substr($key, 0, $vlen) == $var) {
					if (preg_match('/'.$var.'_(.+)_x/', $key, $match)) return $match[1];
				}
			}
		}
		
		return NULL;
	}
	// variablen aus get oder post lesen
	function get_post_var($var)
	{
		//_TODO what about input-pics ?!
		global $HTTP_POST_VARS, $HTTP_GET_VARS;
		return (isset($HTTP_GET_VARS[$var]) ? @$HTTP_GET_VARS[$var] : @$HTTP_POST_VARS[$var]);
	}
	
	function post_var($var)
	{
		global $HTTP_POST_VARS;
		
		if (isset($HTTP_POST_VARS[$var])) {
			return $HTTP_POST_VARS[$var];
		}
		else {
			$vlen = strlen($var);

			foreach($HTTP_POST_VARS as $key => $val) {
				if (substr($key, 0, $vlen) == $var) {
					if (preg_match('/'.$var.'_(.+)_x/', $key, $match)) return $match[1];
				}
			}
		}
	}
	function get_var($var)
	{
		global $HTTP_GET_VARS;
		return $HTTP_GET_VARS[$var];
	}
	
	// 'globale' nachricht hinzuf�gen
	function msg_add($text, $title = '', $type = ICE_MSG_PLAIN)
	{
		ICE::app_push('ic');
		$msg = ICE::sess_get('messages');
		if (!is_array($msg)) $msg = array();
		array_push($msg, array('text' => $text, 'title' => $title, 'type' => $type));
		ICE::sess_set('messages', $msg);
		ICE::app_pop();
	}
	// globale nachricht zur�ckliefern
	// -> array('text', 'title', 'type')
	function msg_get()
	{
		ICE::app_push('ic');
		$msg = ICE::sess_get('messages');
		if (!is_array($msg)) $msg = array();
		$ret = array_shift($msg);
		ICE::sess_set('messages', $msg);
		ICE::app_pop();
		return $ret;
	}
	
	/*
	* nachrichten stack leeren
	*/
	function msg_clear() {
		ICE::app_push('ic');
		ICE::sess_unset('messages');
		ICE::app_pop();
	}
	
	// funktions-zugriffsrechte
	function func_access($func)
	{
		if ((int)ICE::get('usr', 'root') === 1) return true;
		
		//return TRUE;
//		ICE::vd(ICE::get('usr', 'access_rights'));
		return ICE::get('usr', 'access_rights', strtolower($func));
		// funky stuff
	}
	
	// vardump
	function vd($var, $title = 'ICE::vardump', $detail = FALSE, $html_comment = false)
	{
		if ($html_comment) echo '<!--';
		echo '<pre>'.$title.' --- START<br>';
		if ($detail) var_dump($var);
		else print_r($var);
		echo '<br>--- END</pre>';
		if ($html_comment) echo '//-->';
	}
	
	
	/**
	* ist der aktuelle user eingeloggt
	*
	* kopiert dann auch daten von session in ice 
	* und updated last_action des users
	* @return	bool
	*/
	function login_ok()
	{
		global $HTTP_SESSION_VARS;
		
		$result = FALSE;

		// pr�fe session-flag
		$login_flag = ICE::get('ice', 'login_flag');
		if ((!session_is_registered($login_flag)) || ($HTTP_SESSION_VARS[$login_flag] != TRUE)) {
			//$this->err_nr  = ICE_LOGIN_ERR_DENIED;
			return FALSE;
		}

		// login ok, d.h. wir haben einen user
		// also user-daten von session-array (der ice app) in ice laden
		// und last_action updaten (auch in db)
		$last_action = time();
		$usr_info    = array();
		
		ICE::app_push('ice');
		$usr_info = ICE::sess_get('usr');
		$usr_info['last_action'] = $last_action;
		ICE::sess_set('usr', $usr_info);
		ICE::app_pop();
//		ICE::vd($usr_info);
		foreach(array('id', 'email', 'usr', 'firstname', 'lastname', 'short', 'lang', 
			'fullname', 'pwd', 'login', 'last_action', 'ip', 'last_login', 'javascript', 'theme', 'groups', 'root') as $key) {
			ICE::set('usr', $key, $usr_info[$key]);
		}
		
		// auch rechte 'laden'
		ICE::set('usr', 'access_rights', $usr_info['access_rights']);
		
		// und last action des users updaten
		$res = ICE::query('UPDATE '. ICE::get('db', 'table', 'usr') . ' SET state=1, last_action='. time() .' WHERE usr=\''. $usr_info['usr'] .'\'', FALSE);

		$result = TRUE;
		
		return $result;
	}
	
	function domail($to, $subject, $body = '', $header = '')
	{
		if (ICE_SERVER_TYPE == 'local') {	
			echo 'Lokal werden keine mails versendet.';
			echo '<table>'.
				'<tr><td valign="top"><b>To:</b></td><td class="flatform"><pre>'.$to.'</pre></td>'.
				'<tr><td valign="top"><b>Subject:</b></td><td class="flatform"><pre>'.$subject.'</pre></td>'.
				'<tr><td valign="top"><b>Body:</b></td><td class="flatform"><pre>'.$body.'&nbsp;</pre></td>'.
				'<tr><td valign="top"><b>Header:</b></td><td class="flatform"><pre>'.$header.'&nbsp;</pre></td>'.
				'</table>';
		}
		else {
			mail($to, $subject,	$body, $header);
		}
	}

}


//------------------------------------------------------------------------------
// Q: why a static and a 'normal' ice-class ?
// A: static, because you can use it everywhere without globalizing it,
//    and ICE::func() looks much cooler than $my_ice->func()
//    'normal', because you can't save vars (class-pointers etc.) 
//    in a static class
//------------------------------------------------------------------------------

class icIce
{

	// array der infos f�r set()/get()
	var $info = array();
	
	// zeiger auf unsere db-klasse
	var $class_db = NULL;
	
	// zeiger auf unsere session-klasse
	var $class_sess = NULL;
	
	// zeiger auf translation-klasse
	var $class_translate = NULL;
	
	// stack der applicationen
	var $app_stack = array();
	
	// key/values, die an links angeh�ngt werden
	var $link_values = array();
	// link_values url encoded
	var $link_values_enc = array();
	
	function icIce()
	{
		//$this->set(array('ice', 'version', ICE_SYS_VERSION));
	}
	
	// wir bekommen hier ein array
	function set($args)
	{
		
		switch(count($args)) {
			case 3:
				$this->info[$args[0]][$args[1]] = $args[2];
				break;
			case 4:
				$this->info[$args[0]][$args[1]][$args[2]] = $args[3];
				break;
			case 5:
				$this->info[$args[0]][$args[1]][$args[2]][$args[3]] = $args[4];
				break;			
			default:
				echo "??? unknown set";
				die;
		}
	}
	
	function get($args)
	{
		switch(count($args)) {
			case 2:
				return $this->info[$args[0]][$args[1]];
				break;
			case 3:
				return $this->info[$args[0]][$args[1]][$args[2]];
				break;
			case 4:
				return $this->info[$args[0]][$args[1]][$args[2]][$args[3]];
				break;
			default:
				echo "??? unknown get"; print_r($args); die;
		}
	}
	
	
	function error()
	{
	}
	
	function debug()
	{
		echo "<pre>";
		echo "ICE-infos:";
		print_r($this->info);
		echo "</pre>";
	}
}


?>