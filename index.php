<?php
	require_once "inc/session.php";
	
	
	function view_error($message) {
		require_once "inc/config.php";
		global $config;
		
		print $config['header'];
		print "<h3>Error:</h3>";
		print "<h4>$message</h4>";
		print "<p>You should probably click 'Back' in your browser "
			."and correct the problem.</p>\n";
		print $config['footer'];
		
		exit;
	}
	
	
	if (isset($_GET['action'])) {
		require_once "inc/action.php";
		handle_actions($_GET['action']);
	}
	
	require_once "inc/config.php";
	require_once "inc/view.php";
	
	print $config['header'];
	
	// This should be fixed to simply handle_view($_GET['view']);
	// This is here until everything else is fixed for it.
	if (!isset($session->userid)) handle_view('login');
	else if (isset($_GET['view'])) handle_view($_GET['view']);
	else handle_view('default');
	
	print $config['footer'];
?>
