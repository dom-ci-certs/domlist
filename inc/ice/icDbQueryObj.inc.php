<?
/**
* Datenbank-Abfrage-Objekt
*
*
* ein query object wird beim aufruf von ICE::query('...') zurückgeliefert
*
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @version	0.11, 15.10.2001
* @copyright	(c) 2001, 2002 intercoaster.de, www.intercoaster.de
* @access	public
* @history	22.08.02 tre, added param tnr = total number of data in limited queries
* @history	22.08.02 tre, added functions set_tnr - 'set var tnr'
* @history	22.08.02 tre, added functions total_num_rows / tnr - 'return var tnr'
* @package	DB
* @todo		put-funktion (zum schreiben in das array ?)
*/

class icDbQueryObj
{

	/**
	* ergebnis array
	*
	* @var		array
	* @access	private
	*/
	var $data = array();

	/**
	* zeiger auf aktuellen datensatz
	*
	* @var		int
	* @access	private
	*/
	var $data_pnt = -1;
	
	var $tnr      = 0;

	/**
	* Konstruktor
	*
	*/
	function icDbQueryObj()
	{
	}
	
	/**
	* daten setzen
	*
	* wird von der db-klasse aufgerufen, und mit daten gefüllt
	*
	* @param	array	$arr
	* @access	public
	*/
	function set($arr)
	{
		$this->data = $arr;
		reset($this->data);
		$this->data_pnt = -1;
	}

	function set_tnr($val)
	{
		$this->tnr = $val;
	}
	
	/**
	* erhöht den datensatz-zeiger um 1
	*
	* liefert FALSE, wenn kein weiterer satz da, TRUE sonst
	*
	* @return	bool
	* @access	public
	*/
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
	
	
	/**
	* liefert die daten des feldes des aktuellen satzes
	*
	* @param	string	name des feldes
	* @access	public
	*/
	function field($name)
	{
		if ($this->data_pnt == -1) {
			return $this->data[0][$name];
		}
		else {
			return $this->data[$this->data_pnt][$name];
		}
	}
	
	/**
	* kurzform von field()
	*
	* @param	string	name des feldes
	* @see		field()
	* @access	public
	*/
	function f($name)
	{
		return $this->field($name);
	}

	/**
	* liefert alle felder des aktuellen satzes
	*
	* @return	array
	* @access	public
	*/
	function record()
	{
		if ($this->data_pnt == -1) {
			return $this->data[0];
		}
		else {
			return $this->data[$this->data_pnt];
		}
	}
	
	
	/**
	* kurzform von record()
	*
	* @return	array
	* @see		record()
	* @access	public
	*/
	function r()
	{
		return $this->record();
	}
	
	
	/**
	* liefert die anzahl der sätze
	*
	* @return	int
	* @access	public
	*/
	function num_rows()
	{
		return count($this->data);
	}
	
	
	/**
	* kurzform vom num_rows()
	*
	* @return	int
	* @see		num_rows()
	* @access	public
	*/
	function nr()
	{
		return $this->num_rows();
	}
	
	/**
	* liefert die anzahl der sätze (bei query mit limit ohne beachtung des limit)
	*
	* @return	int
	* @access	public
	*/
	function total_num_rows()
	{
		return $this->tnr;
	}
	
	
	/**
	* kurzform vom total_num_rows()
	*
	* @return	int
	* @see		num_rows()
	* @access	public
	*/
	function tnr()
	{
		return $this->total_num_rows();
	}

	
	
	/**
	* setzt den datenzeiger auf den anfang
	*
	* @access	public
	*/
	function reset()
	{
		$this->data_pnt = -1;
		reset($this->data);
	}
	
	/**
	* liefert alle sätze
	*
	* @return	array
	* @access	public
	*/
	function get_all()
	{
		return $this->data;
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
		
		$util = ICE::create('util');
		
		while($this->next()) {
			$ret .= '<option value="'. $util->plain2form($this->f($val)) .'"';
			$ret .= ($this->f($val) == $selected) ? ' SELECTED' : '';
			$ret .= '>'.$this->f($title).'</option>';
		}
		
		$this->reset();
		
		return $ret;
	}
	
	function clear()
	{
		$this->data = array();
		$this->reset();
	}
}
?>