<?php
	require_once "inc/config.php";
	
	
	session_name($config['session_name']);
	session_start();
	
	
	// Keep session data in a single object.
	class Session {
		var $view;
		
		function __construct() {
			$this->view = 'login';
		}
	}
	
	
	if(!isset($_SESSION['session'])) {
		$_SESSION['session'] = new Session();
	}
	
	// $_SESSION['session'] is a bit too long so
	// we define a reference for it.
	$session =& $_SESSION['session'];
?>
