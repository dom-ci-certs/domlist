<?php
	require(dirname(__FILE__) . '/inc/conf.php');
	require(dirname(__FILE__) . '/inc/domlist/domlistServer.class.php');
	
	$dls = new domlistServer();
	$dls->dispatch();
		
