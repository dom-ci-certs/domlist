<?

/**
* icDataObject
*
*
* ist eine erweitertes db-query objekt (aber nicht durch vererbung <-> speed (?))
*
* @copyright	2001, 2002 intercoaster oHG
* @version	0.46	04.02.2002
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @access	public
* @history	15.08.02 jkl, rückgabeverhalten von lock() geändert (siehe dort)
* @history	15.08.02 jkl, neue funktion field_names()
* @history	11.07.02 jkl, neue funktion debug(bool), bis jetzt nur auswirkung auf insert() update() delete() select()
* @history	24.04.02 jkl, neue funktion sql() (fuer reines sql)
* @history	04.02.02 jkl, insert() setzt auch sys_usr_id_created und sys_date_created, falls nicht gesetzt
* @history	05.12.01 tre, delete() löscht auch versionen, falls flag gesetzt und trashcan auf False
* @history	19.11.01 jkl, neue funktion version_select()
* @history	23.10.01 jkl, neue funktion notice_select()
* @history	22.10.01 jkl, neue funktionen notice_insert(), notice_get(), notice_clear()
* @history	22.10.01 jkl, neue funktionen set_flag(), restore_flag()
* @history	19.10.01 jkl, unterstützung für ICE_FTYPE_DATE/TIME
* @history	19.10.01 jkl, delete() kann (bei gesetztem trashcan-flag) auch in den muelleimer legen. anpassung bei select(), liest keine gelöschten seiten
* @history	18.10.01 jkl, neue funktionen _cop2arc(), version_get(). update(), insert() berücksichtigen versionierbarkeit
* @history	15/16.10.01 jkl, neue funktionen lock(), unlock(), clear_locks()
* @history	15.10.01 jkl, neue funktion html_options()
* @history	14.10.01 jkl, man kann jetzt mehere primary_key felder angeben (in conf-file ein array)
* @history	14.10.01 jkl, neue funktionen _key2sql(), _val2sql()
* @history	14.10.01 jkl, zahl-felder die leer sind, und das auch sein dürfen, werden auf '0' gesetzt
* @history	22.08.02 tre, added param tnr = total number of data in limited queries
* @history	22.08.02 tre, added functions set_tnr - 'set var tnr'
* @history	22.08.02 tre, added functions total_num_rows / tnr - 'return var tnr'
* @history	04.12.02 tre, added function notice_delete = delete notice entry 
* @history	24.22.04 jkl, added acl support
* @todo	needs some code-cleanup here and there, and massiv documentation
* @package	ICE
*/
class icDataObject
{
	//_TODO	needs some code-cleanup here and there
	// ergebnis array
	var $data = array();

	// data-pointer
	var $data_pnt = -1;
	
	// info-array
	var $info = array();
	
	// original flags
	var $orig_flags;
	
	// fehlerhafte felder
	var $err_fields = array();
	
	// diverse texte
	var $txt = array();
	
	// sql-debu-ausgabe
	var $debug = FALSE;
	
	// last insert id
	var $insert_id = 0;
	
	/**
	* zeiger für interne utility-klasse
	*
	* @var	object
	* @access	private
	*/
	var $_util_class;


	var $tnr = 0;

	function icDataObject()
	{
		$this->txt = array(
			'err_empty'  => ICE::lang('error: empty field'),//'Feld ist leer',
			'err_format' => ICE::lang('error: wrong format'),//'falsches Format',
		);
	}
	
	/*
	* sql debug modus an/ausschalten
	*
	* @param	bool
	*/
	function debug($debug = FALSE)
	{
		$this->debug = $debug;
	}
	
	
	// initialisieren
	function init($info)
	{
		$this->info = $info;
		
		$this->orig_flags = array(
			'trashcan' => $info['trashcan'],
			'version'  => $info['version'],
			'notice'  => $info['notice'],
		);
	}
	
	// ersetzungen in template machen
	function tpl_assign(&$tpl, $prefix = '')
	{
		if ($prefix == '') $prefix = $this->info['type'];
		
		$util = ICE::create('util');
		
		$ass = array();
		
		// titel ersetzen
		foreach($this->info['fields'] as $name => $field) {
			$ass[strtoupper($prefix.'_title_short_'.$name)] = $field['title_short'];
		}
		
		// form-werte ersetzen
		foreach($this->info['fields'] as $name => $field) {
			if (isset($field['form']) && $field['form'] == FALSE) continue;
		
			switch($field['type']) {
				case ICE_FTYPE_DATE_TIME:
					$date = $this->f($name);
					
					if ($date<1 && $field['auto_date'] == TRUE) $date = time();
					$ass[strtoupper($prefix.'_form_value_'.$name)] = $date;
					$ass[strtoupper($prefix.'_form_value_'.$name.'_hour')]   = $util->html_options_date(ICE_UTIL_DATE_HOUR, @date('H', $date));
					$ass[strtoupper($prefix.'_form_value_'.$name.'_minute')] = $util->html_options_date(ICE_UTIL_DATE_MINUTE, @date('i', $date));
				case ICE_FTYPE_DATE:
					$date = $this->f($name);
					if ($date<1  && $field['auto_date'] == TRUE) $date = time();
					$ass[strtoupper($prefix.'_form_value_'.$name)] = $date;
					$ass[strtoupper($prefix.'_form_value_'.$name.'_day')]   = $util->html_options_date(ICE_UTIL_DATE_DAY, @date('d', $date));
					$ass[strtoupper($prefix.'_form_value_'.$name.'_month')] = $util->html_options_date(ICE_UTIL_DATE_MONTH, sprintf('%02d', @date('m', $date)));
					$ass[strtoupper($prefix.'_form_value_'.$name.'_year')]  = $util->html_options_date(ICE_UTIL_DATE_YEAR, @date('Y', $date));
					break;
				default:
					$ass[strtoupper($prefix.'_form_value_'.$name)] = $util->db2form($this->f($name));
			}
		}

		// html-werte ersetzen
		foreach($this->info['fields'] as $name => $field) {
			if (isset($field['form']) && $field['form'] == FALSE) continue;
			
			switch($field['type']) {
				case ICE_FTYPE_DATE_TIME:
					$date = $this->f($name);
					if ($date<1 && $field['auto_date'] == TRUE) $date = time();
					$ass[strtoupper($prefix.'_html_value_'.$name)] = $date;
					$ass[strtoupper($prefix.'_html_value_'.$name.'_hour')]   = @date('H', $date);
					$ass[strtoupper($prefix.'_html_value_'.$name.'_minute')] = @date('i', $date);
				case ICE_FTYPE_DATE:
					$date = $this->f($name);
					if ($date<1  && $field['auto_date'] == TRUE) $date = time();
					$ass[strtoupper($prefix.'_html_value_'.$name)] = $date;
					$ass[strtoupper($prefix.'_html_value_'.$name.'_day')]   = @date('d', $date);
					$ass[strtoupper($prefix.'_html_value_'.$name.'_month')] = @date('m', $date);
					$ass[strtoupper($prefix.'_html_value_'.$name.'_year')]  = @date('Y', $date);
					break;
				default:
					$ass[strtoupper($prefix.'_html_value_'.$name)] = $util->db2html($this->f($name));
			}
		}
		
		$tpl->assign($ass);
	}
	
	
	// daten-pool leeren
	function clear()
	{
		$this->reset();
		$this->data = array();
	}
	
	// daten aus formular lesen
	// name ist der name des arrays mit daten in HTTP_XXX_VARS
	function read_form($name, $method = 'post')
	{
		
		switch(strtolower($method)) {
			case 'get':
				global $HTTP_GET_VARS;
				$from = $HTTP_GET_VARS[$name];
				break;
			case 'post':
			default;
				global $HTTP_POST_VARS;
				$from = $HTTP_POST_VARS[$name];
		}
		
		$arr  = array();
		$util = ICE::create('util');
		
		foreach($this->info['fields'] as $name => $field) {
			if (isset($field['form']) && $field['form'] == FALSE) continue;
			switch($field['type']) {
				case ICE_FTYPE_DATE:
					$arr[$name] = mktime(0, 0, 0, $from[$name.'_month'], $from[$name.'_day'], $from[$name.'_year']);
					break;
				case ICE_FTYPE_DATE_TIME:
					$arr[$name] = mktime($from[$name.'_hour'], $from[$name.'_minute'], 0, $from[$name.'_month'], $from[$name.'_day'], $from[$name.'_year']);
					break;
				default:
					$arr[$name] = $util->form2plain($from[$name]);
			}
		}
		
		$this->set($arr);
	}
	
	/*
	set(key, val, [offs])
	set(array(), [offs])
	*/
	
	// werte setzen
	function set($arr, $val = '', $offset = 0)
	{
		if (is_array($arr)) {
			if ($val === '') $offset = 0;
			else $offset = $val;
			foreach($arr as $key => $val) $this->data[$offset][$key] = $val;
		}
		else {
			$this->data[$offset][$arr] = $val;
		}
		reset($this->data);
		$this->data_pnt = -1;
	}
	
	function set_all($arr)
	{
		$this->data = $arr;
	}
	
	function set_tnr($val)
	{
		$this->tnr = $val;
	}
	
	// ersten satz validieren
	// abhängig von $mode = {enter|edit|delete}
	// unterschied: bei enter darf/muss der PRIMARY_KEY leer sein
	function valid($mode = 'edit', $fields = '', $include = TRUE)
	{
		// welche felder sollen behandelt werden
		$do_fields = $this->_field_list($fields, $include);
		
		$mode = strtolower($mode);
		
		$valid = ICE::create('validate');
		
		$this->err_fields = array();
		
		
		foreach($do_fields as $key) {
			$val = $this->data[0][$key];
			
			// bei enter ist das ID-feld egal
			if (($mode == 'enter') && ($key == $this->info['primary_key'])) continue;
			
			// ist es leer und darf es das sein
			if ((trim($val) == '')) {
				if (!$this->info['fields'][$key]['empty']) $this->err_fields[] = array('field' => $key, 'err' => ICE_FTYPE_ERR_EMPTY);
				continue;
			}
			
			// ist es seinem typ entsprechend
			if (!$valid->valid($val, $this->info['fields'][$key]['type'])) {
				$this->err_fields[] = array('field' => $key, 'err' => ICE_FTYPE_ERR_FORMAT);
				continue;
			}
			
			// extra regex
			if ($this->info['fields'][$key]['regex']) {
				if (!preg_match($this->info['fields'][$key]['regex'], $val)) {
					$this->err_fields[] = array('field' => $key, 'err' => ICE_FTYPE_ERR_FORMAT);
				}
			}
		}
		
		if (count($this->err_fields) == 0) return TRUE;
		else return FALSE;
		
	}
	
	// 1. datensatz aus db löschen
	// die fields gelten hier für die where-klausel und sind nicht optional !
	function delete($fields, $include = TRUE)
	{
		// welche felder sollen behandelt werden
		$do_fields = $this->_field_list($fields, $include);
		
		$util = ICE::create('util');
		
		
		// where-klausel bauen
		$sql_where = '';
		$delim     = '';
		foreach($do_fields as $field) {
			$value = $util->plain2db($this->data[0][$field]);
			$type  = $this->info['fields'][$field]['type'];
			
			$sql_where  .= $delim.$field.'=';
			
			if (($type == ICE_FTYPE_DIGIT) || ($type == ICE_FTYPE_DATE) || ($type == ICE_FTYPE_TIME) || ($type == ICE_FTYPE_DATE_TIME)) {
				if ($value == '') $value = 0;
				$sql_where .= $value;
			}
			else {
				$sql_where .= '\''.$value.'\'';
			}
			$delim = ' AND ';
		}
		
		
		if ($this->info['trashcan'] == TRUE) {
			// satz komplett einlesen
			$this->select('*', TRUE, $sql_where);

			// trashcan-flag setzen
			$tr_flag = ICE::query('UPDATE '. $this->info['table'] .' SET sys_trashcan='. ICE::get('usr', 'id').' WHERE '.$sql_where);
			if ($tr_flag->nr() == 0) return FALSE;

			
			// hidden+show-felder lesen
			$info_hidden = $info_show = array();
			foreach($this->info['fields'] as $name => $field) {
				if ($field['trashcan_info_hidden'] == TRUE) {
					$info_hidden[$field['title_short']] = $this->f($field['db']);
				}
				if ($field['trashcan_info_show'] == TRUE) {
					$info_show[$field['title_short']] = $this->f($field['db']);
				}
			}
					
			$trash = ICE::gateway('ic_trashcan');
			$del = $trash->delete(array(
				'app_name'    => ICE::get('ice', 'app', 'name'),
				'dob_name'    => $this->info['type'],
				'dob_id'      => $this->_key2sql(),
				'info_show'   => $info_show,
				'info_hidden' => $info_hidden,
			));
			
			// dob leeren
			$this->clear();
			return $del;
		}
		else {
			
			$sql = 'DELETE FROM '. $this->info['table'] .' WHERE ';
			$res = ICE::query($sql.' '.$sql_where);
			
			
			// versionen löschen, falls vorhanden, und alle pk's übergeben wurden
			// und Flag gesetzt
			if ($this->info['version']) {
				
				// alle pk's in fields ?
				$tmp_pk = $this->info['primary_key'];
				if (!is_array($tmp_pk)) $tmp_pk = array($tmp_pk);
				sort($do_fields);
				sort($tmp_pk);
				if (implode('', $tmp_pk) == implode('', $do_fields)) {

					$sql_version_where = array();
					foreach($tmp_pk as $key) {
						$sql_version_where[] = 'sys_base_'.$key.'='.$this->_val2sql($key, $this->f($key));
					}

					$sql_version = 'DELETE FROM '.$this->info['table'] .'_arc '.
						'WHERE '. implode(' AND', $sql_version_where);
						
					$res_version = ICE::query($sql_version, $this->debug);
				}
				
				//echo $sql_version.' '.$sql_where_version;
			}
			if ($res->nr() == 0) return FALSE;
			else return TRUE;
		}
		

	}
	
	// 1. datensatz in db einfügen
	// optional, welche felder eingefügt werden sollen oder nicht
	function insert($fields = '', $include = TRUE)
	{
		// welche felder sollen behandelt werden
		$do_fields = $this->_field_list($fields, $include);
		
		// das primary-key feld kommt hier nicht mit rein,
		// wenn auto_id gesetzt und nicht FALSE ist
		$pk = $this->info['primary_key'];
		if (is_array($pk)) {
			foreach($pk as $key) {
				if (isset($this->info['fields'][$key]['auto_id']) && ($this->info['fields'][$key]['auto_id'] != 'FALSE')) {
					unset($do_fields[$key]);
				}
			}
		}
		else {
			if (isset($this->info['fields'][$pk]['auto_id']) && ($this->info['fields'][$pk]['auto_id'] != FALSE)) {
				unset($do_fields[$this->info['primary_key']]);
			}
		}

		
		
		// prüfen, ob notice-bar, dann alte notice loeschen
		if ($this->info['notice'] == TRUE) {
			$this->notice_clear();
		}
		
		
		// prüfen, ob versionierbar, dann version_id=1
		if ($this->info['version'] == TRUE) $this->set('sys_version_id', '1');

		// user/date created setzen
		if (!$this->f('sys_usr_id_created')) $this->set('sys_usr_id_created', ICE::get('usr', 'id'));
		if (!$this->f('sys_date_created')) $this->set('sys_date_created', time());

		
		// user/date changed setzen
		$this->set('sys_usr_id_changed', ICE::get('usr', 'id'));
		if (!$this->f('sys_date_changed')) {
			$this->set('sys_date_changed', time());
		}


		
		$sql_keys = implode(', ', $do_fields);
		
		$util = ICE::create('util');
		
		$komma = '';
		$sql_values = '';
		foreach ($do_fields as $field)
		{
			$value = $util->plain2db($this->data[0][$field]);
			$type  = $this->info['fields'][$field]['type'];
			
			$sql_values .= $komma;
			if (($type == ICE_FTYPE_DIGIT) || ($type == ICE_FTYPE_DATE) ||
				($type == ICE_FTYPE_TIME) || ($type == ICE_FTYPE_DATE_TIME)) {
				if ($value == '') $value = 0;
				$sql_values .= $value;
			}
			else {
				$sql_values .= '\''.$value.'\'';
			}
			$komma = ', ';
		}
		
		$res = ICE::query('INSERT INTO '. $this->info['table'] .' ('.$sql_keys.') VALUES ('.$sql_values.')', $this->debug);
		
		if ($res->nr() == 0) return FALSE;
		else {
			$this->insert_id = mysql_insert_id();
			return TRUE;
		}
	}

	// 1. datensatz in db updaten
	// optional, welche felder eingefügt werden sollen oder nicht
	function update($fields = '', $include = TRUE)
	{
		// welche felder sollen behandelt werden
		
		$do_fields = $this->_field_list($fields, $include);

		// der primary-key wird hier für die where-klausel gebraucht
		// aber nicht als update-wert
		$pk = $this->info['primary_key'];
		if (is_array($pk)) {
			foreach($pk as $key) unset($do_fields[$key]);
		}
		else {
			unset($do_fields[$pk]);
		}
		
		$util = ICE::create('util');
		
		
		// prüfen, ob notice-bar, dann alte notice loeschen
		if ($this->info['notice'] == TRUE) {
			$this->notice_clear();
		}
		
		// wenn es versionierbar ist ...
		if ($this->info['version'] == TRUE) {
			// kopie des alten satzes in arc-table machen
			// wir kriegen die neue versionsnummer zurueck
			$new_version = $this->_copy2arc();
			//echo "NEUE VERSION:$new_version:";
			$this->set('sys_version_id', $new_version);
		}
		else {
			$this->set('sys_version_id', '1');
		}

		// user/date changed setzen
		$this->set('sys_usr_id_changed', (int)ICE::get('usr', 'id'));
		$this->set('sys_date_changed', time());

		$komma = '';
		$sql = 'UPDATE '.$this->info['table']. ' SET ';
		foreach ($do_fields as $field)
		{
			$value = $util->plain2db($this->data[0][$field]);
			$type  = $this->info['fields'][$field]['type'];
			
			$sql .= $komma.$field.'=';
			if (($type == ICE_FTYPE_DIGIT) || ($type == ICE_FTYPE_DATE) ||
				($type == ICE_FTYPE_TIME) || ($type == ICE_FTYPE_DATE_TIME) ||
				($type == ICE_FTYPE_ID)) {
				if ($value == '') $value = 0;
				$sql .= $value;
			}
			else {
				$sql .= '\''.$value.'\'';
			}
			$komma = ', ';
		}
		
		$sql .= ' WHERE '. $this->_key2sql();

		$res = ICE::query($sql, $this->debug);
		if ($res->nr() == 0) return FALSE;
		else return TRUE;
	}
	
	// datensätze lesen
	function select($fields, $include, $where = '', $order = '', $result_start = 0, $result_limit = 0, $acl_right = '')
	{
		//$this->debug = true;
		
		// haben wir acl-beschraenkung und user ist nicht 'root'
		$have_acl = ($acl_right != '' && isset($this->info['acl']) && $this->info['acl'] != '' && (int)ICE::get('usr', 'root') !== 1);
		
		$prefix_fields = ($have_acl) ? 'dob.' : '';
		$postfix_table = ($have_acl) ? ' AS dob' : '';
		
		$do_fields = $this->_field_list($fields, $include, $prefix_fields);
		
		$sql_fields = implode(', ', $do_fields);
		$sql_where  = ($where) ? ' WHERE '.$where    : '';
		$sql_order  = ($order) ? ' ORDER BY '.$order : '';
		
		// gibts hier trashcan-flag ? die lesen wir nich aus
		if ($this->info['trashcan'] == TRUE) {
			$sql_where .= ($sql_where == '') ? ' WHERE '.$prefix.'sys_trashcan<=0' : ' AND '.$prefix.'sys_trashcan<=0';
		}
		
		// acl beschraenkung
		$acl_table = $acl_group = '';
		if ($have_acl) {
			$acl = ICE::create('acl');
			$acl->load_conf($this->info['acl']);
			
			$acl_table  = ', ' . $acl->table . ' AS acl';
			//$acl_group  = $acl->sql_group();
			$acl_where  = $acl->sql_where('dob', 'acl', $this->info['primary_key'], $acl->get_right($acl_right));
			
			$sql_where .= (($sql_where == '') ? ' WHERE ' : ' AND ' ) . $acl_where;
			$sql_order = ' GROUP BY '.$prefix_fields.$this->info['primary_key'] . ' ' . $sql_order;
		}
		
		$res = ICE::query('SELECT '. $sql_fields .' FROM '. $this->info['table'] . $postfix_table.' ' . $acl_table . 
							$sql_where . $acl_group . $sql_order, 
							$this->debug, FALSE, $result_start, $result_limit);
							
		$this->clear();
		$this->set_all($res->get_all());
		$this->set_tnr($res->tnr());
	}
	
	// pure-sql
	function sql($sql, $debug = FALSE, $ignore_err = FALSE, $result_start = 0, $result_limit = 0)
	{
		$res = ICE::query($sql, $debug, $ignore_err, $result_start, $result_limit );
		
		$this->clear();
		$this->set_all($res->get_all());
		$this->set_tnr($res->tnr());
	}
	
	// bekommt eine feldliste, und ein flag, ob diese felder
	// berücksichtigt oder ausgeschlossen werden
	function _field_list($fields = '', $include = TRUE, $prefix = '')
	{
		
		$orig = $ret = array();
		foreach($this->info['fields'] as $field => $dummy) $orig[$field] = $prefix.$field;
		
		if (is_array($fields)) {
			if ($include) {
				foreach($orig as $field) if (in_array($field, $fields)) $ret[$field] = $prefix.$field;
			}
			else {
				foreach($orig as $field) if (!in_array($field, $fields)) $ret[$field] = $prefix.$field;
			}

		}
		elseif ($fields == '*') {
			$ret = $orig;
		}
		elseif ($fields != '') {
			if ($include) {
				$ret = array($fields => $fields);
			}
			else {
				unset($orig[$fields]);
				$ret = $orig;
			}
		}
		else {
			$ret = $orig;
		}
		
		return $ret;
	}
	
	
	// fehler-felder auslesen
	function err_fields()
	{
		return $this->err_fields;
	}
	
	// fehler-felder in liste schreiben
	// welchen titel nehmen
	function err_fmt($title = 'title_short')
	{
		$ret = '<ul>';
		foreach($this->err_fields as $err) {
			$ret .= '<li>'.$this->field_info($err['field'], $title).', ';
			switch($err['err']) {
				case ICE_FTYPE_ERR_EMPTY:
					$ret .= $this->txt['err_empty'];
					break;
				case ICE_FTYPE_ERR_FORMAT:
				default:
					$ret .= $this->txt['err_format'];
			}
			$ret .= '</li>';
		}
		$ret .= '</ul>';
		return $ret;
	}
	
	// field-infos auslesen
	function field_info($field = '', $info = '')
	{
		if ($info == '') {
			return $this->info['fields'][$field];
		}
		else {
			return $this->info['fields'][$field][$info];
		}
	}
	
	// namen der felder liefern
	function field_names()
	{
		return array_keys($this->info['fields']);
	}
	
	function next()
	{
		if (count($this->data) == 0) return FALSE;
		// if we didnt even start, do so
		if ($this->data_pnt == -1) {
			$this->data_pnt = 0;
			return TRUE;
		}
		elseif (next($this->data)) {
			$this->data_pnt++;
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
	
	function field($name)
	{
		if ($this->data_pnt == -1) {
			return $this->data[0][$name];
		}
		else {
			return $this->data[$this->data_pnt][$name];
		}
	}

	// shorthand for field
	function f($name)
	{
		return $this->field($name);
	}

	// ganzen satz liefern
	function record()
	{
		if ($this->data_pnt == -1) {
			return $this->data[0];
		}
		else {
			return $this->data[$this->data_pnt];
		}
	}
	// shorthand for record()
	function r()
	{
		return $this->record();
	}
	
	function num_rows()
	{
		return count($this->data);
	}
	
	// shorthand for num_rows
	function nr()
	{
		return $this->num_rows();
	}
	
	function total_num_rows()
	{
		return $this->tnr;
	}
	
	// shorthand for total_num_rows
	function tnr()
	{
		return $this->total_num_rows();
	}
	
	function reset()
	{
		$this->data_pnt = -1;
		reset($this->data);
	}
	
	/**
	* html-option tags generieren
	* 
	* @param	string	aus welchen feldern sollen die keys bestehen
	* @param	string	aus welchen feldern sollen die values bestehen
	* @param	string	welcher key ist selected
	* @return	string	html-option-tags
	* @access	public
	*/
	function html_options($val, $title, $selected = '')
	{
		$ret = '';
		$this->reset();
		
		while($this->next()) {
			$ret .= '<option value="'. $this->f($val) .'"';
			$ret .= ($this->f($val) == $selected) ? ' SELECTED' : '';
			$ret .= '>'.$this->f($title).'</option>';
		}
		
		$this->reset();
		
		return $ret;
	}
	
	/**
	* datensatz locken
	*
	* @return	bool
	* @access	public
	*/
	function lock()
	{
		// lies lock-field
		$lock = ICE::query('SELECT sys_usr_id_locked from '. $this->info['table'] .' WHERE '.$this->_key2sql(), FALSE);
		if ($lock->nr() == 0) return FALSE;
		
		$lock_id = $lock->f('sys_usr_id_locked');
		$usr_id  = ICE::get('usr', 'id');
		
		// locken geht, wenn noch nicht gelockt, oder akt. user der locker ist
		if (($lock_id <= 0) || ($lock_id == $usr_id)) {
			// locken theor. moeglich
			$do_lock = ICE::query('UPDATE '. $this->info['table'] .
				' SET sys_usr_id_locked='.$usr_id.', sys_date_locked='. time() .
				' WHERE '. $this->_key2sql(), FALSE);
			// wenn nix geändert wurde, würde es FALSE liefern
			return TRUE;
			//return ($do_lock->nr() > 0) ? TRUE : FALSE;
		}
		else {
			// kein locken möglich
			return FALSE;
		}
	}
	
	
	/**
	* datensatz unlocken
	*
	* @return	bool
	* @access	public
	*/
	function unlock()
	{
		// lies lock-field
		$lock = ICE::query('SELECT sys_usr_id_locked from '. $this->info['table'] .' WHERE '.$this->_key2sql(), FALSE);
		if ($lock->nr() == 0) return FALSE;

		$lock_id = $lock->f('sys_usr_id_locked');
		$usr_id  = ICE::get('usr', 'id');

		// satz kann nur vom user ge-unlockt werden, der ihn auch gelockt hat !
		if ($lock_id == $usr_id) {
			// unlocken theor. moeglich
			$do_unlock = ICE::query('UPDATE '. $this->info['table'] .
				' SET sys_usr_id_locked=0, sys_date_locked=0'.
				' WHERE '. $this->_key2sql(), FALSE);
			return ($do_unlock->nr() > 0) ? TRUE : FALSE;
		}
		else {
			// kein unlocken möglich
			return FALSE;
		}
	}
	
	/**
	* abgelaufene locks entfernen
	*
	* @return	bool
	* @access	public
	*/
	function clear_locks()
	{
		$clean_locks = ICE::query('UPDATE '. $this->info['table'].
			' SET sys_usr_id_locked=0, sys_date_locked=0'.
			' WHERE sys_date_locked<'.(time()-ICE::get('ice', 'lock', 'timeout')), FALSE);
		return ($clean_locks->nr() > 0) ? TRUE : FALSE;
	}
	
	
	
	
	/**
	* den oder die primary_keys in sql liefern (vom ersten datensatz)
	* 
	* @return	string	AND-verknuepfter sql-string
	* @access	private
	*/
	function _key2sql()
	{
		$pkey = $this->info['primary_key'];
		
		$ret = array();
		
		if (is_array($pkey)) {
			foreach($pkey as $key) {
				$pvalue = $this->_val2sql($key, $this->data[0][$key]);
				$ret[]  = $key.'='.$pvalue;
			}
		}
		else {
			$pvalue = $this->_val2sql($pkey, $this->data[0][$pkey]);
			$ret[]  = $pkey.'='.$pvalue;
		}
		
		return implode(' AND ', $ret);
	}
	
	/**
	* ein wert in sql-wandeln
	*
	* @param	string	feld des wertes
	* @param	string	zu wandelnder wert
	* @return	string	gewandelter wert
	*/
	function _val2sql($field, $val)
	{
		$ftype = $this->info['fields'][$field]['type'];
		$ret   = '';
		
		if (($ftype == ICE_FTYPE_DIGIT) || ($ftype == ICE_FTYPE_DATE) ||
				($ftype == ICE_FTYPE_TIME) || ($ftype == ICE_FTYPE_DATE_TIME) ||
				($ftype == ICE_FTYPE_ID))
		{
				$ret = ($val == '')  ? '0' : $val;
		}
		else {
			if (!$this->_util_class) $this->_util_class = ICE::create('util');
			$ret = '\''.$this->_util_class->plain2db($val).'\'';
		}

		return $ret;
	}
	
	
	/**
	* kopie des alten satzes in arc-table machen, als version. neue version_id zurueck
	*
	* @return	int	neue version_id
	* @access	private
	*/
	function _copy2arc()
	{
		$util = ICE::create('util');
		
		
		// alten satz komplett auslesen 
		$old = ICE::query('SELECT * FROM '. $this->info['table'].' WHERE '. $this->_key2sql(), FALSE);
		if ($old->nr() == 0) return FALSE;

		
		// vorhandene felder
		$orig = array();
		foreach($this->info['fields'] as $field => $dummy) $orig[$field] = $field;

		
		// die original primary_keys werden entfernt
		$pk = $this->info['primary_key'];
		if (!is_array($pk)) $pk = array($pk);
		foreach($pk as $key) unset($orig[$key]);
		
		// lock-felder brauchen wir auch nicht
		unset($orig['sys_usr_id_locked']);
		unset($orig['sys_date_locked']);
		
		// aus feldern sql bauen
		$sql_values = $sql_keys = array();
		foreach($orig as $field) {
			$value = $util->plain2db($old->f($field));
			$type  = $this->info['fields'][$field]['type'];

			if (($type == ICE_FTYPE_DIGIT) || ($type == ICE_FTYPE_DATE) || ($type == ICE_FTYPE_TIME) || ($type == ICE_FTYPE_DATE_TIME)) {
				if ($value == '') $value = 0;
				$sql_values[] = $value;
			}
			else {
				$sql_values[] = '\''.$value.'\'';
			}
			$sql_keys[] = $field;
		}
		
		
		// alte keys umbenennen in sys_base_...
		foreach($pk as $key) {
			$sql_keys[]   = 'sys_base_'.$key;
			$sql_values[] = $this->_val2sql($key, $old->f($key));
		}
		
		// notice_id
		$sql_keys[]   = 'sys_notice_id';
		$sql_values[] = '0';
		
		
		// alten datensatz backupen
		$sql = 'INSERT INTO '. $this->info['table'] .'_arc ('.implode(',', $sql_keys).') VALUES ('.implode(',', $sql_values).')';
		//echo "<tt>ARCHIV:</tt>";
		ICE::query($sql, FALSE);
		
		$version_id = $old->f('sys_version_id');
		return ++$version_id;
		
	}
	
	
	/**
	* alte versionen eines dobs auslesen
	*
	* @param	string	order-by-db-field (optional)
	* @param	string	order-direction-db (optional)
	* @return	object	neues dob-objekt mit den versions
	* @access	public
	*/
	function version_get($order_by = 'sys_version_id', $order_dir = 'desc')
	{
		// primary-keys bestimmen
		$pk = $this->info['primary_key'];
		if (!is_array($pk)) $pk = array($pk);
		
		$sql_keys = array();
		foreach($pk as $key) {
			$pvalue     = $this->_val2sql($key, $this->data[0][$key]);
			$sql_keys[] = 'sys_base_'.$key.'='.$pvalue;
		}
		
			
		// versionen auslesen
		$sql = 'SELECT * FROM '. $this->info['table'].'_arc WHERE '.implode(' AND ', $sql_keys);
		if ($this->info['notice'] == TRUE) $sql .= ' AND sys_notice_id=0 ';
		$sql .= ' ORDER BY '. $order_by .' '. $order_dir;
					
		$ver = ICE::query($sql, FALSE);
		
		// neues dob-objekt erstellen und initialisieren
		$me = get_class($this);
		$ne = new $me;
		$ne->init($this->get_init());
		
		// versionen reinschreiben
		$ne->set_all($ver->get_all());
		
		return $ne;
	}
	/**
	* eine versionen eines dobs auslesen
	*
	* @param	int	id (in der arc-tabelle)
	* @return	object	neues dob-objekt mit der version
	* @access	public
	*/
	function version_select($version_id)
	{
		// primary-keys bestimmen
		$pk = $this->info['primary_key'];
		if (!is_array($pk)) $pk = array($pk);
		
		$sql_keys = array();
		foreach($pk as $key) {
			$pvalue     = $this->_val2sql($key, $this->data[0][$key]);
			$sql_keys[] = 'sys_base_'.$key.'='.$pvalue;
		}
		
			
		// versionen auslesen
/*		$sql = 'SELECT * FROM '. $this->info['table'].'_arc WHERE '.implode(' AND ', $sql_keys);
		if ($this->info['notice'] == TRUE) $sql .= ' AND sys_notice_id=0 ';
		$sql .= ' AND id='.$version_id;
*/
		$sql = 'SELECT * FROM '. $this->info['table'].'_arc WHERE id='.$version_id;
					
		$ver = ICE::query($sql, FALSE);
		
		// neues dob-objekt erstellen und initialisieren
		$me = get_class($this);
		$ne = new $me;
		$ne->init($this->get_init());
		
		// versionen reinschreiben
		$ne->set_all($ver->get_all());

		return $ne;
	}


	/**
	* flags setzen
	*
	* @param	string	flag
	* @param	string	value
	* @access	public
	*/
	function set_flag($flag, $value)
	{
		switch($flag) {
			case 'trashcan':
				$this->info['trashcan'] = $value;
				break;
			case 'version':
				$this->info['version']  = $value;
				break;
			case 'notice':
				$this->info['notice']  = $value;
				break;
		}
	}

	/**
	* flags auf original werte setzen
	*
	* @param	string	flag
	* @access	public
	*/
	function restore_flag($flag)
	{
		switch($flag) {
			case 'trashcan':
				$this->info['trashcan'] = $this->orig_flags['trashcan'];
				break;
			case 'version':
				$this->info['version']  = $this->orig_flags['version'];
				break;
			case 'notice':
				$this->info['notice']  = $this->orig_flags['notice'];
				break;
		}
	}

	function get_init()
	{
		return $this->info;
	}
	
	function notice_clear()
	{
		if (!$this->info['notice']) return FALSE;
		$clear = ICE::query('DELETE FROM '.$this->info['table'].'_arc WHERE sys_usr_id_changed='. ICE::get('usr', 'id').' AND sys_notice_id!=0', FALSE);
		return $clear->nr();
	}
	
	/**
	* eintrag des aktuellen satzes in arc-tabelle machen, als notice
	*
	* @return	int	neue notice_id
	* @access	private
	*/
	function notice_insert()
	{
		// original 'retten'
		$old_data = $this->data;
		
		$util = ICE::create('util');
		
		
		// vorhandene felder
		$orig = array();
		foreach($this->info['fields'] as $field => $dummy) $orig[$field] = $field;

		
		// die original primary_keys werden entfernt
		$pk = $this->info['primary_key'];
		if (!is_array($pk)) $pk = array($pk);
		foreach($pk as $key) unset($orig[$key]);
		
		// lock-felder brauchen wir auch nicht
		unset($orig['sys_usr_id_locked']);
		unset($orig['sys_date_locked']);
		
		// aber die changed felder
		$this->set('sys_usr_id_changed', ICE::get('usr', 'id'));
		$this->set('sys_date_changed', time());
		
		// version ist dann immer auf 0
		$this->set('sys_version_id', '0');
		
		// vorhandene, max-notice_id auslesen
		$max_notice = 0;
		$max_note = ICE::query('SELECT max(sys_notice_id) as sys_notice_id FROM '. $this->info['table']. '_arc'.
				' WHERE sys_usr_id_changed='. ICE::get('usr', 'id') .' AND sys_version_id=0', FALSE);
		if ($max_note->nr() > 0) $max_notice = $max_note->f('sys_notice_id');
		$max_notice++;
		//echo "<tt>NEUE NOTICE:$max_notice:</tt>";
		
		
		
		// aus feldern sql bauen
		$sql_values = $sql_keys = array();
		foreach($orig as $field) {
			$value = $util->plain2db($this->f($field));
			$type  = $this->info['fields'][$field]['type'];

			if (($type == ICE_FTYPE_DIGIT) || ($type == ICE_FTYPE_DATE) || ($type == ICE_FTYPE_TIME) || ($type == ICE_FTYPE_DATE_TIME)) {
				if ($value == '') $value = 0;
				$sql_values[] = $value;
			}
			else {
				$sql_values[] = '\''.$value.'\'';
			}
			$sql_keys[] = $field;
		}
		
		
		// alte keys umbenennen in sys_base_...
		foreach($pk as $key) {
			$sql_keys[]   = 'sys_base_'.$key;
			$sql_values[] = $this->_val2sql($key, $this->data[0][$key]);
		}
		
		// notice_id
		$sql_keys[]   = 'sys_notice_id';
		$sql_values[] = $max_notice;
		
		
		// alten datensatz backupen
		$sql = 'INSERT INTO '. $this->info['table'] .'_arc ('.implode(',', $sql_keys).') VALUES ('.implode(',', $sql_values).')';
		//echo "<tt>ARCHIV:</tt>";
		ICE::query($sql, FALSE);
		
		
		// original widerherstellen
		$this->data = $old_data;
		$this->reset();
		
		return $max_notice;
		
	}
	
	
	function notice_get()
	{
		return ICE::query('SELECT sys_notice_id, sys_date_changed FROM '. $this->info['table'] .
				'_arc WHERE sys_usr_id_changed='. ICE::get('usr', 'id') .' AND sys_version_id=0 ORDER BY sys_notice_id DESC', FALSE);
	}

	function notice_delete($notice_id)
	{
		return ICE::query('DELETE FROM '. $this->info['table'] .
				'_arc WHERE sys_usr_id_changed='. ICE::get('usr', 'id') .' AND sys_version_id=0 AND sys_notice_id='. (int)$notice_id, FALSE);
	}

	function notice_select($notice_id)
	{
		if ($notice_id == '') $notice_id = 0;
		
		$notice = ICE::query('SELECT * FROM '. $this->info['table'] .'_arc' .
				' WHERE sys_usr_id_changed='. ICE::get('usr', 'id') .' AND sys_version_id=0 AND sys_notice_id='. $notice_id, FALSE);
		
		$this->clear();
		$this->set_all($notice->get_all());
		
		// alte p-keys wiederherstellen
		$pk = $this->info['primary_key'];
		if (!is_array($pk)) $pk = array($pk);
		foreach($pk as $key) $this->set($key, $this->f('sys_base_'.$key));

	}
	
	function get_all()
	{
		return $this->data;
	}

}
?>