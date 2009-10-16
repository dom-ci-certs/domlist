<?
/**
* ice-db klasse f�r MySQL
*
*
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @version	0.044, 04.09.2002
* @copyright	(c) 2001, 2002, 2003 intercoaster.de, www.intercoaster.de
* @access	public
* @history	22.08.02 tre, added params result_start, result_limit in function query (limiting result array)
* @history	04.09.03 jkl, added fctn. set(), get(), get_tables()
* @package	DB
*/



// db-query-object
include_once(dirname(__FILE__).'/icDbQueryObj.inc.php');

class icDbmysql
{

	// db connection
	var $link_id = NULL;	
	
	// halt on errors ?
	var $err_halt = TRUE;
	
	// print_err messages ?
	var $err_verbose = TRUE;
	
	// mysql error-message
	var $err_msg  = "";
	
	// mysql error-number
	var $err_nr   = 0;
	
	// output debug-infos ?
	var $debug = FALSE;
	
	// name of db-query class
	
	function icIceDbMysql()
	{
	}
	
	
	function connect()
	{
		if ($this->link_id == NULL) {
			$this->link_id = @mysql_pconnect(ICE::get('db', 'host'), ICE::get('db', 'user'), ICE::get('db', 'pwd'))
					or $this->error('could not connect to '.ICE::get('db', 'host'));
		
			@mysql_select_db(ICE::get('db', 'name'), $this->link_id)
					or $this->error('could not select '.ICE::get('db', 'name'));
		}
	}			
	
	function query($sql, $debug = FALSE, $ignore_err = FALSE, $result_start = 0, $result_limit = 0)
	{
		global $_dbcache;
		
		
		//ICE::vd( $result_limit ,'RL' );
		if (trim($sql) == '') {
			return FALSE;
		}
		
		
		//$debug = TRUE;
		$this->connect();
		if ($debug || $this->debug) {
			echo '<p>MySql Debug:<tt>'.$sql.'</tt>:</p>';
		}
		
		// do query
		if ($ignore_err) {
			$query_id = @mysql_query($sql, $this->link_id);
			if (!$query_id) return FALSE;
		}
		else {
			$query_id = @mysql_query($sql, $this->link_id) or $this->error("invalid sql:".$sql);
		}
		
		// create and fill new query object
		$arr = array();
		$new_obj = new icDbQueryObj;
		if (preg_match("/^(select|show)\s+/i", $sql)) {
			while ($tr = @mysql_fetch_array($query_id, MYSQL_ASSOC)) {
				$arr[] = $tr;
			}
			$arr = $result_limit ?  array_slice( $arr, $result_start , $result_limit ) : $arr;
			$new_obj->set_tnr((int)@mysql_num_rows($query_id));
		}
		else {
			// update, delete, insert
			$cnt = @mysql_affected_rows();
			for ($i = 1; $i <= $cnt; $i++, $arr[] = "") { }
		}
		
		$new_obj->set($arr);
		
		// free db result
		unset($arr);
		@mysql_free_result($query_id);
				
		return $new_obj;
			
	}
	
	// liefert alle tabellen der aktuellen datenbank
	function get_tables() {
		$res = $this->query('SHOW TABLES FROM '.ICE::get('db', 'name'));

		$ret = array();
		while($res->next()) {
			$ret[] = $res->f('Tables_in_'.ICE::get('db', 'name'));
		}
		return $ret;
	}
	
	function set($var, $val) {
		switch($var) {
			case 'err_halt':		// halt on errors
			case 'err_verbose':		// print out errors
			case 'debug':			// print out sql-statements
				$this->$var = (bool)$val;
				break;
			case 'err_msg':
				$this->err_msg = $val;
				break;
		}
	}
	function get($var) {
		switch($var) {
			case 'err_halt':		// halt on errors
			case 'err_verbose':		// print out errors
			case 'debug':			// print out sql-statements
			case 'err_msg':
				return $this->$var;
				break;
		}
	}

	function error($msg)
	{
	    $this->err_msg = @mysql_error($this->link_id);
    	$this->err_nr  = @mysql_errno($this->link_id);
	
		if ($this->err_verbose) {
			$ice_ok = false; //class_exists('ICE');
			
			if ($ice_ok) {
				$gui = ICE::create('gui');
				$test = $gui->msg_box('test', '', '');
				if (trim($test) == '') $ice_ok = FALSE;
			}
			
			$txt  = '<html><head><title>Datenbank-Fehler</title>';
			$txt .= ($ice_ok) ? $gui->css() : '';
			$txt .= '</head><body></td></tr></table></div></span></p>';
			
			echo $txt;
			
			$err  = '<h3>Datenbank-Fehler</h3>';
			$err .= '<p>'.$msg.'<p>';
			$err .= '<p>MySql error:'.$this->err_msg.' ('.$this->err_nr.')</p>';

			if ($this->err_halt) {
				$err .= '<p><b>Der Administrator wurde von diesen Fehler benachrichtigt.</b></p>';
				//mail()
			}
			
			if ($ice_ok) {
				echo $gui->msg_box($err, 'Es trat ein Datenbank-Fehler auf', ICE_GUI_MSG_ERROR) ;
			}
			else {
				echo $err;
			}
			
			echo '</body></html>';
		}
		
		if ($this->err_halt) {
			if (!$this->err_verbose) echo '<p>application stopped.</p>';
			exit;
		}
	}
				
}

?>