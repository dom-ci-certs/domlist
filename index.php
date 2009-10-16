<?php
	require(dirname(__FILE__) . '/inc/conf.php');
	require(dirname(__FILE__) . '/inc/class.domlist_server.php');
	
	$dls = new domlist_server();
	$dls->dispatch();
		
