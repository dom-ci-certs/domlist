<?
/**
* diverse hilfsfunktionen
*
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @version	0.43, 28.02.2002
* @copyright	(c) 2001, 2002 intercoaster.de, www.intercoaster.de
* @access	public
* @package	ICE
* @history	28.02.02, jkl	added function str_chop()
* @history	19.04.02, jkl	added function html_options()
* @history	23.10.02, jkl	added functions get_tags(), tag_params(), make_tag()
* @history	14.05.03, jkl	changed function form2plain() to test for array
* @history	16.06.03, jkl	is_array-check in html_options()
* @history	21.07.03, jkl	added second param to plain2form()
*/
	
	define('ICE_UTIL_DATE_DAY',    20);
	define('ICE_UTIL_DATE_MONTH',  21);
	define('ICE_UTIL_DATE_YEAR',   22);
	define('ICE_UTIL_DATE_HOUR',   23);
	define('ICE_UTIL_DATE_MINUTE', 24);
	
	class icIceUtil
	{
	
		function icIceUtil()
		{
		}

		function form2db($val)
		{
			if ($this->magic_quotes_on()) {
				$val = stripslashes($val);
			}
			
			return addslashes($val);
		}
		
		function db2html($val)
		{
			$val = htmlentities($val);
			$val = nl2br($val);
			$val = stripslashes($val);

			return $val;
		}

		function plain2html($val, $allow_html = FALSE)
		{
			if ($allow_html) {
				$trans = get_html_translation_table(HTML_ENTITIES);
				unset($trans['>']); unset($trans['<']);
				unset($trans['"']); unset($trans['\'']);
				//$val = strtr($val, $trans);
			}
			else {
				//$val = htmlentities($val);
				//$val = htmlspecialchars($val); 	// das geht nicht bei polnisch !
				$val = str_replace(array('>', '<', '"'), array('&gt;', '&lt;', '&quot;'), $val);
			}
			$val = nl2br($val);

			return $val;
		}
		
		function plain2db($val)
		{
			return addslashes($val);
		}
		
		function db2form($val)
		{
			$val = stripslashes($val);
			$val = str_replace('"', "&quot;", $val);
			
			return $val;
		}
		
		/*
		* string fuer formular-feld umwandeln
		*
		*@param	string	$val
		*@param	bool	$quotes	escape double-quotes
		*/
		function plain2form($val, $quotes = true)
		{
			if ($quotes) $val = str_replace('"', "&quot;", $val);
			
			return $val;
		}
		
		
		function db2plain($val)
		{
			$val = stripslashes($val);
			return $val;
		}
		
		
		function form2plain($val)
		{
			if ($this->magic_quotes_on() && !is_array($val)) {
				$val = stripslashes($val);
			}

			return $val;
		}
		
		//_TODO bei den datums-/zeitfunktionen soll irgendwann eine user-einstellung beruecksichtigt werden
		function ts2date($ts)
		{
			return date('d.m.Y', $ts);
		}
		
		function ts2datetime($ts)
		{
			return date('d.m.Y H:i', $ts);
		}

		function ts2datetimesec($ts)
		{
			return date('d.m.Y H:i:s', $ts);
		}

		
		function magic_quotes_on()
		{
			return true;
			if (get_magic_quotes_runtime() || get_magic_quotes_gpc()) return TRUE;
			else return FALSE;
		}
		
		function html_options($key_val, $selected_key = '',$order=FALSE)
		{
			$html = '';
			if (!is_array($key_val)) return '';
			
			if ($order) {
				asort($key_val);
			}
			foreach($key_val as $key => $val) {
				$html .= '<option value="'.$this->plain2form($key).'"';
				if ((string)$key == $selected_key) {
					$html .= ' SELECTED';
				}
				$html .= '>'.$this->plain2html($val).'</option>';

			}
	
			return $html;
		}
		
		function html_options_date($type, $selected = '')
		{
			$arr = array();
			switch($type) {
				case ICE_UTIL_DATE_DAY:
					$arr = range(1, 31);
					break;
				case ICE_UTIL_DATE_MONTH:
					$arr = range(1, 12);
					break;
				case ICE_UTIL_DATE_YEAR:
					$arr = range(1999, 2020);
					break;
				case ICE_UTIL_DATE_HOUR:
					$arr = range(0, 23);
					break;
				case ICE_UTIL_DATE_MINUTE:
					for ($i=0; $i<=55; $arr[] = sprintf('%02d', $i), $i+=5);
					$selected = intval($selected/5)*5;
					break;
			}

			$html = '';
			foreach($arr as $val) {
				$html .= '<option value="'.$val.'"';
				if ($val == $selected) $html .= ' SELECTED';
				$html .= '>'.$val.'</option>';
			}
			
			return $html;
		}

		
		function generate_url($url)
		{
			if (!preg_match('#^(http://|https://|mailto:|ftp://|javascript:).+$#six', $url)) {
				$url = 'http://'.$url;
			}
			return $url;
		}
		
		// chops a string to the given length
		// will include word-boundary check later
		//_TODO look in online/icoaster/do_search.php, there it is !
		function str_chop($str, $length)
		{
			if (strlen($str) > $length) {
				return substr($str, 0, $length).' ...';
			}
			else {
				return $str;
			}
		}

		
		/**
		* liefert alle tags in einem string
		*
		* problem: wenn das zeichen '>' in einem wert steht
		*
		*@param	string	$code	string in dem tags gesucht werden
		*@param	mixed	$type	string oder array mit zu suchenden tags
		*@return	array	array mit kompletten tags
		*/
		function get_tags($code, $tag)
		{
			$look = $ret = array();
			
			if (!is_array($tag)) $tag = array($tag);
			foreach ($tag as $t) $look[] = str_replace('/', '\/', preg_quote($t));
		
			//ICE::vd('/(<(?:'.implode('|', $look).')(?:\s+[^>]*>|>))/i', 'GET_TAGS');
			preg_match_all('/(<(?:'.implode('|', $look).')(?:\s+[^>]*>|>))/i', $code, $match);
			return $match[1];
		}
		
		/**
		* generiert einen tag aus namen und parametern
		*
		*@param	string	$tag	name des tags
		*@param array	$params	array von parametern
		*/
		function make_tag($tag, $params = '')
		{
			$ret = '<'.strtolower($tag);
			
			if (is_array($params)) {
				$add = array();
				foreach($params as $key => $val) {
					$add[] = $key.'="'.$val.'"';
				}
				$ret .= ' '.implode(' ', $add);
			}
			
			$ret .= '>';
			return $ret;
		}
		
		/**
		* liefert alle parameter eines tags
		*
		* berücksichtigt werte in einfachen/doppelten oder keinen anfz.
		* und werte-lose params
		*
		*@param	string	$tag	kompletter tag-string
		*@return	array	array('parameter' => 'wert')
		*/
		function tag_params($tag)
		{
			$ret = array();
			// tag namen und klammern entfernen
			$tag = trim(preg_replace('/^<[a-z]+ (.*) >$/ix', '\\1', $tag)).' ';
			
			preg_match_all('/ ([a-z]+) (?: \s*=\s* (?: ([^"\'][^\s]*)  |   ("|\')((?:\\\.|[^\\\])*?)\\3 ) | \s+)/ix', $tag, $match);
			// 0=orig, 1=param, 2=wert ohne trennz., 3=trennz., 4=wert in trennz.
			foreach ($match[0] as $nr => $dummy) {
				$val = ($match[2][$nr]) ? $match[2][$nr] : $match[4][$nr];
				$ret[$match[1][$nr]] = $val;
			}
			return $ret;
		}

		function strip_mso_tags($code)
		{
			$not_allowed = array(
				'?xml:namespace', 
				'v:shape', '/v:shape', 'v:textbox', '/v:textbox',
				'o:p', '/o:p',
			);
			
			$code = str_replace('class=MsoNormal', '', $code);
			
			foreach($this->get_tags($code, $not_allowed) as $t) {
				$code = str_replace($t, '', $code);
			}
			
			
			return $code;
		}
	}

?>