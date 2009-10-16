<?php
	$ice = dirname(__FILE__).'/ice/';
	
	require($ice.'/icIce.inc.php');
	require($ice.'/icDbmysql.inc.php');
	require($ice.'/icCsv.inc.php');
	
	$_myice->class_db = new icDbmysql();
	
	ICE::set('db', 'table_prefix', '');

	ICE::set('db', 'host', 'localhost');
	ICE::set('db', 'name', 'domlist');
	ICE::set('db', 'user', 'domlist');
	ICE::set('db', 'pwd',  '***');
	ICE::set('db', 'type', 'mysql');
	ICE::set('db', 'search_placeholder', '%');
	
	
?>