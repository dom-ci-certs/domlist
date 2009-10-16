<?php
/**
* Im- und Export von CSV-Dateien
*
* @author	Thorsten Reiter <t.reiter@intercoaster.de>
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @version	03.09.2002
* @copyright	(c) 2002, 2003 intercoaster.de, www.intercoaster.de
* @access	public
* @package	FILES
*/

class icCsv
{
	/*-- variables --*/
	var $field_delimiter = ';';    // Feldtrennzeichen
	var $field_enclosure = '"';     // Feldbergrenzer

	var $row_delimiter   = "\n"; // Zeilentrennzeichen
	var $escape_char     = '"';

	//
	var $chars_to_escape = '"';
	var $escape_type     = '';

	// Konstruktor (opt. Parameter setzen) 
	function icCsv($field_delimiter = FALSE,$field_enclosure = FALSE , $row_delimiter = '',
				   $escape_char = FALSE, $chars_to_escape = '') {
		if ($field_delimiter !== FALSE) $this->field_delimiter = $field_delimiter;
		if ($field_enclosure !== FALSE) $this->field_enclosure = $field_enclosure;
		if ($row_delimiter) 			$this->row_delimiter   = $row_delimiter;
		if ($escape_char   !== FALSE)   $this->escape_char     = $escape_char;
		if (is_array($chars_to_escape))
			$this->chars_to_escape = $chars_to_escape;
		else if ($chars_to_escape) 
			$this->chars_to_escape = array($chars_to_escape);
		else
			$this->chars_to_escape = is_array($this->chars_to_escape) ? $this->chars_to_escape : array($this->chars_to_escape);
	}

	/**
	*	export: braucht Daten-Array, liefert String
	*/
	function export($data) {
		$return_str = '';
		if ($this->escape_type == 'mso') {
			return $this->export_mso($data);
		}
		foreach ($data as $row) {
			foreach ($row as $key=>$field) {
					foreach ($this->chars_to_escape as $val) {
						$row[$key] = str_replace($val,$this->escape_char.$val,$row[$key]);
					}
					$row[$key] = $this->field_enclosure . $row[$key]. $this->field_enclosure;
			}
			$return_str .= implode ($this->field_delimiter, $row);
			$return_str .= $this->row_delimiter;
		}
		return $return_str;
	}

	/**
	*	export_mso: braucht Daten-Array, liefert String
	*/
	function export_mso($data) {
		$return_str = '';
		foreach ($data as $row) {
			foreach ($row as $key=>$field) {
				if (strpos($field, $this->escape_char)     !== FALSE ||
					strpos($field, $this->field_delimiter) !== FALSE ||
					strpos($field, $this->row_delimiter)   !== FALSE) {
					$row[$key] = str_replace($this->escape_char,
						$this->escape_char.$this->escape_char,$field);
					$row[$key] = $this->field_enclosure . $row[$key]. $this->field_enclosure;
				}
			}
			$return_str .= implode ($this->field_delimiter, $row);
			$return_str .= $this->row_delimiter;
		}
		return $return_str;
	}

	/**
	*
	*/
	function download_export($data, $filename) {
		Header('Cache-control: private');
		Header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$filename.'.csv');
		echo $this->export($data);
		exit;
	}

	/**
	*	Import: braucht locales File, liefert Daten-Array oder FALSE
	*/
	function import_file($file) {
		$data_array = array();
		$row = 1;

		if ( $fp  = @fopen ($file, "r")) {
			// all is fine
		} else {
			//echo "FILE ERR<br>$file";
			return FALSE;
		};
// ab php 4.3.0
//		while ($data = fgetcsv ($fp, 1000, $this->field_delimiter, $this->field_enclosure)) {
		while ($data = fgetcsv ($fp, 50000, $this->field_delimiter)) {
			$data_array[] = $data;
		}
		fclose ($fp);
		return $data_array;
	}

	/**
	*	Import: braucht locales File, liefert ???
	*/
	function _import_file($file, $table, $fields) {
		if ($this->escape_type == 'def') {
			
		}
		if ($this->escape_type == 'mso') {
			
		}
	}
	
	function set_settings($settings) {
		 $this->field_delimiter = isset($settings['field_delimiter']) ? $settings['field_delimiter'] : $this->field_delimiter;
		 $this->field_enclosure = isset($settings['field_enclosure']) ? $settings['field_enclosure'] : $this->field_enclosure;
		 $this->row_delimiter   = isset($settings['row_delimiter'])   ? $settings['row_delimiter']   : $this->row_delimiter;
		 $this->escape_char     = isset($settings['escape_char'])     ? $settings['escape_char']     : $this->escape_char;
		 $this->chars_to_escape = isset($settings['chars_to_escape']) ? $settings['chars_to_escape'] : $this->chars_to_escape;
		 $this->escape_type     = isset($settings['escape_type'])     ? $settings['escape_type']     : $this->escape_type;
	}
	
	function get_settings() {
		$settings['field_delimiter'] = $this->field_delimiter;
		$settings['field_enclosure'] = $this->field_enclosure;
		$settings['row_delimiter']   = $this->row_delimiter;
		$settings['escape_char']     = $this->escape_char;
		$settings['chars_to_escape'] = $this->chars_to_escape;
		$settings['escape_type']     = $this->escape_type;
		return $settings;
	}

	/**
	*  vordefinierte Einstellungen für im- und export
	*/
	function load_settings($name) {
		switch ($name) {
			case 'mso':
				 $this->field_delimiter = ';';    // Feldtrennzeichen
				 $this->field_enclosure = '"';    // Feldbergrenzer
				 $this->row_delimiter   = "\n";   // Zeilentrennzeichen
				 $this->escape_char     = '"';    
				 $this->chars_to_escape = array('"');
				 $this->escape_type     = 'mso';
				break;
			default:
			case 'default':
				 $this->field_delimiter = ';';
				 $this->field_enclosure   = '"';
				 $this->row_delimiter   = "\n";
				 $this->escape_char     = '\\';
				 $this->chars_to_escape = array('"','\'');
				 $this->escape_type     = 'def';
				break;
		}
	}

	//
	function get_load_sql($file, $table, $fields, $replace=FALSE) {
	    $query     = 'LOAD DATA LOCAL INFILE \'' . $file . '\'';
		// escapen ???
		if (get_magic_quotes_gpc()) {
	    	// TODO
		} else {
	    	// TODO
		}
    	if ($replace === TRUE) {
        	$query .= ' REPLACE';
    	}
    	$query     .= ' INTO TABLE ' . $table;
    	if ($this->field_delimiter) {
        	$query .= ' FIELDS TERMINATED BY \'' . $this->field_delimiter . '\'';
    	}
    	
    	if ($this->field_enclosure) {
        	$query .= ' ENCLOSED BY \'' . $this->field_enclosure . '\'';
    	}
   		if ( $this->escape_char ) {
        	$query .= ' ESCAPED BY \'' .  $this->escape_char . '\'';
    	}
    	if ($this->row_delimiter){
        	$query .= ' LINES TERMINATED BY \'' . $this->row_delimiter . '\'';
    	}
    	if (is_array($fields)) {
            $query .= ' (' . $column_name . ')';
        }
		return $query;
	}
}