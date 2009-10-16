<?
/**
* werte-validierung
*
* 
*
* @author	Joachim Klinkhammer <j.klinkhammer@intercoaster.de>
* @version	0.32, 05.06.2001
* @copyright	(c) 2001 intercoaster oHG, www.intercoaster.de
* @history	02.07.09, jkl, changed return-typ to BOOL for all validate-types (i.e. preg_match() returns an integer)
* @access	public
* @package	ICE
*/


/** this is old and should not be used ! use from ICE
*
* @var	const
*/
define('ICE_VAL_EMAIL',  1);
/** this is old and should not be used ! use from ICE
*
* @var	const
*/
define('ICE_VAL_USR',    2);
/** this is old and should not be used ! use from ICE
*
* @var	const
*/
define('ICE_VAL_PWD',    3);
/** this is old and should not be used ! use from ICE
*
* @var	const
*/
define('ICE_VAL_NUMBER', 4);
	
	class icIceValidate
	{
		/**
		* konstruktor
		*/
		function icIceValidate()
		{
		}
		
		/**
		* wert an hand seines typs validieren
		*
		* @param	string	zu validierender wert
		* @param	const	typ des wertes
		* @return	bool
		* @access	public
		*/
		function valid($var, $type)
		{
			switch($type) {
				case ICE_FTYPE_EMAIL:
				case ICE_VAL_EMAIL:
					return (preg_match('/^[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,4}$/i', $var)) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_USR:
				case ICE_VAL_USR:
					return (preg_match('/^[a-z0-9_öäüß]{3,255}$/i', $var)) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_PWD:
				case ICE_VAL_PWD:
					$special = preg_quote('!§$%&?*+-_.,:;<>');
					return (preg_match('/^[a-z0-9'.$special.']{5,255}$/i', $var)) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_NUMBER:
				case ICE_VAL_NUMBER:
					//return preg_match('/^[0-9\-\.\,e]+$/', $var);
					
//					return preg_match('/^\-? \d+                (,\d+)? $/x', $var);
//					return preg_match('/^\-? \d{1,3} (\.\d{3})* (,\d+)? $/x', $var);
					
					return (preg_match('/^-?\d{1,3}((\.\d{3})*|\d+)(,\d+)?$/', $var)) ? TRUE : FALSE;
					
					break;
				case ICE_FTYPE_ID:				
				case ICE_FTYPE_DIGIT:
					return (preg_match('/^[0-9]+$/', $var)) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_TEXT:
					return (preg_match('/^.{1,255}$/', $var)) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_TEXTFIELD:
					return TRUE;
					break;
				case ICE_FTYPE_DATE:
					//_UNCLEAR from timestamp
					return (checkdate(date('m', $var), date('d', $var), date('Y', $var))) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_DATE_TIME:
					return (checkdate(date('m', $var), date('d', $var), date('Y', $var))) ? TRUE : FALSE;
					break;
				case ICE_FTYPE_ON_OFF:
					return TRUE;
					break;
				case ICE_FTYPE_PHONE:
					return TRUE;
					break;
				default:
					echo "->".$type."<-";
					echo '<p><b>need type for validation !</b></p>';
					return FALSE;
			}
		}
	}
?>