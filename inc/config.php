<?php

/*
 * BASIC CONFIGURATION
*/
// Connection string given to pg_connect
$config['connstr'] = 'dbname=bugs';

// Administrator contact email address.
$config['admin'] = 'tvoipio@cc.hut.fi';

// Server address, used for links when sending mail
$config['server'] = 'wasteland.pp.htv.fi';

// Wastebug mail address, used when sending email notifications
$config['email'] = "wastebug@{$config['server']}";

// Where are we relative to virtual server root ?
$config['path'] = "/wastebug/index.php";

// Name of the session cookie
// This is only useful if you run several installations of Wastebug
$config['session_name'] = "WASTEBUG";

// Title displayed in titlebar and as logo's alt-text
$config['title'] = 'Wastebug';
// Logo displayed on top of page
$config['logo'] = 'wastebug.png';

// This is here so it's possible to add internal rules,
// but you should try to keep it simple or people won't read it.
$config['checklist'] = <<<EOT
<ol>
	<li>What DOES happen and what SHOULD have happened?</li>
	<li>How to reproduce the problem? How often does it happen?</li>
	<li>In exactly which <b>version</b> of the product does it happen?</li>
	<li>What platform/computer? Put that in the <b>computer</b> field.</li>
</ol>
EOT;

// Number of news entries to show on main page.
$config['newslimit'] = 5;

// Width of input boxes
// You probably don't want to change this though.
$config['width'] = 350;

/*
 * MOSTLY INTERNAL STUFF BELOW
*/
// DO NOT CHANGE: Release script fills in correct version automatically.
$config['version'] = '0.9.1';

$config['header'] = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{$config['title']}</title>
		<style type="text/css">@import url("wastebug.css");</style>
	</head>
	<body>
		<h1><a href="{$config['path']}"
		><img src="{$config['logo']}" alt="{$config['title']}" /></a>
		</h1>

EOT;

$config['footer'] = <<<EOT
		<hr />
		<p class="footer">
		This is Wastebug ver. {$config['version']}.
		Report problems to 
		<a href="mailto:{$config['admin']}">{$config['admin']}</a>.
		</p>
	</body>
</html>

EOT;

?>
