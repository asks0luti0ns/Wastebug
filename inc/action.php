<?php
	require_once "inc/database.php";
	
	
	// Redirect, default is front page
	// Notice that this never returns.
	function redirect($page = 0) {
		global $config;
		
		if ($page) header("Location: {$config['path']}?$page");
		else header("Location: {$config['path']}");
		exit;
	}
	
	
	function redirect_bug($bug) {
		redirect("view=bug&bugid=$bug");
	}
	
	
	// Take a message, readily quoted, and turn
	// http, https, and ftp urls into links.
	// Do the same with mail addresses.
	//
	// Also do word-wrapping here, so we get it right.
	// Must be done before any processing.
	// Notice: we want non-escaped stuff here.
	//
	function action_format_message($message) {
		global $config;
		
		if (!$message) return false;
		if (preg_match('/^\s*$/', $message)) return false;
		$message = wordwrap($message, 80);
		$message = htmlspecialchars($message);
		$message = preg_replace(
			'/(https?|ftp):\/\/[^ \n\r\t\"]+/',
			'<a href="\0">\0</a>', $message);
		$message = preg_replace(
			'/bug:([0-9]+)/',
			"<a href=\"{$config['path']}?"
			. "view=bug&bugid=\\1\">bug:\\1</a>", $message);
		$message = preg_replace(
			'/[^ \n\r\t\"]+@[^ \n\r\t\"]+/',
			'<a href="mailto:\0">\0</a>', $message);
		return "<pre>\n$message\n</pre>";
	}
	
	
	// Basicly the same as action_format_message,
	// but this one is for email, so don't bother with HTML
	// Mainly a placeholder for future extensions
	//
	function action_format_message_mail($message) {
		if (!$message) return false;
		$message = wordwrap($message, 80);
		
		return $message;
	}
	
	
	// Mail sender
	function action_edit_mail($bug, $user, $action, $info) {
		global $db, $config;
		
		$bugdata = $db->get_bug($bug);
		$bugname = $bugdata->name;
		$userdata = $db->get_user($user);
		$username = $userdata->fullname;
			
		$date = date("Y-m-d H:i:s");
		
		$message = "Case $bug: $bugname\n"
				. "http://{$config['server']}{$config['path']}?"
				. "view=bug&bugid=$bug\n--\n"
				. "$username $action on $date:\n\n$info\n\n";
		
		$tousers = $db->get_subscriptions($bug);
		
		foreach ($tousers as $u) {
			if (!$u->email) continue;
			mail($u->email,
				"[Wastebug] Case $bug: $bugname",
				$message,
				array(
					'From'         => $config['email'],
					'Content-type' => 'text/plain; charset=utf-8'
				));
		}
	}
	
	
	function action_login() {
		global $session, $db;
		
		$loginname = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
		$pass      = isset($_POST['password']) ? md5($_POST['password'])              : '';
		
		$user = $db->login($loginname, $pass);
		
		if (!$user) view_error("Invalid username or password.");
		$session->userid   = $user->id;
		$session->super    = $user->super == 't' ? true : false;
		$session->username = $loginname;
	}
	
	
	function action_adduser() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$loginname = isset($_POST['name'])     ? htmlspecialchars($_POST['name'])     : '';
		$pass1     = isset($_POST['pass1'])    ? $_POST['pass1']                      : '';
		$pass2     = isset($_POST['pass2'])    ? $_POST['pass1']                      : '';
		$fullname  = isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '';
		$email     = isset($_POST['email'])    ? htmlspecialchars($_POST['email'])    : '';
		$super     = isset($_POST['super'])    ? ($_POST['super'] ? true : false)     : false;
		
		if ($pass1 != $pass2) view_error("Passwords didn't match.");
		if (!$pass1) view_error("Empty password.");
		if (!$loginname) view_error("Empty username.");
		
		$db->create_user($loginname, md5($pass1), $fullname, $email, $super);
		redirect("view=admin");
	}
	
	
	function action_addproject() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
		$owner = isset($_POST['owner']) ? (int) $_POST['owner'] : NULL;
		
		if (!$name) view_error("Empty project name.");
		
		$db->create_project($name, $owner);
		redirect("view=admin");
	}
	
	
	function action_edituser() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$uid       = isset($_POST['uid'])      ? ((int) $_POST['uid'])                : NULL;
		$loginname = isset($_POST['name'])     ? htmlspecialchars($_POST['name'])     : '';
		$pass1     = isset($_POST['pass1'])    ? $_POST['pass1']                      : '';
		$pass2     = isset($_POST['pass2'])    ? $_POST['pass2']                      : '';
		$fullname  = isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '';
		$email     = isset($_POST['email'])    ? htmlspecialchars($_POST['email'])    : '';
		$super     = isset($_POST['super'])    ? ($_POST['super'] ? true : false)     : '';
		
		if ($pass1 != $pass2) view_error("Passwords didn't match.");
		if (!$loginname) view_error("Empty username.");
		if ($pass1) $db->set_password($uid, md5($pass1));
		
		$db->edit_user($uid, $loginname, $fullname, $email, $super);
		redirect("view=admin");
	}
	
	
	function action_editproject() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$pid   = isset($_POST['pid']) ? ((int) $_POST['pid'])            : NULL;
		$name  = isset($_POST['pid']) ? htmlspecialchars($_POST['name']) : '';
		$owner = isset($_POST['pid']) ? ((int) $_POST['owner'])          : NULL;
		
		if (!$name) view_error("Empty project name.");
		$newuserdata = $db->get_user($owner);
		if ($newuserdata->enabled == "f")
			view_error("Can't make disabled user project owner.");
		
		$db->edit_project($pid, $name, $owner);
		redirect("view=admin");
	}
	
	
	function action_enable_user() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$user = isset($_GET['uid']) ? ((int) $_GET['uid']) : NULL;
		
		$db->set_user_enabled($user, true);
		redirect("view=admin");
	}
	
	
	function action_disable_user() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$user = isset($_GET['uid']) ? ((int) $_GET['uid']) : NULL;
		$userbugs = $db->users_bugs($user, 0);
		if (count($userbugs) != 0) view_error("Can't disable user with cases assigned.");
		if ($db->has_projects($user)) view_error("Can't disable user who owns projects.");
		if ($user == $session->userid) view_error("You can't disable yourself.");
		
		$db->set_user_enabled($user, false);
		redirect("view=admin");
	}
	
	
	function action_delproject() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$projectid = isset($_GET['delid']) ? ((int) $_GET['delid']) : NULL;
		
		$db->delete_project($projectid);
		redirect("view=admin");
	}
	
	
	function action_password() {
		global $session, $db;
		
		$pass1 = isset($_POST['pass1']) ? $_POST['pass1'] : '';
		$pass2 = isset($_POST['pass2']) ? $_POST['pass2'] : '';
		$opass = isset($_POST['opass']) ? $_POST['opass'] : '';
		
		if (!$db->login($session->username, md5($opass))) {
			view_error("Invalid (old) password.");
		}
		
		if ($pass1 != $pass2) view_error("Passwords didn't match.");
		if (strlen($pass1) < 4) view_error("Password too short.");
		
		$db->set_password($session->userid, md5($pass1));
	}
	
	
	function action_postnews() {
		global $session, $db;
		
		if (!$session->super) redirect();
		
		$title   = isset($_POST['title'])   ? htmlspecialchars($_POST['title']) : '';
		$content = isset($_POST['content']) ? $_POST['content']                 : '';
		
		$content = action_format_message($content);
		
		$db->post_news($session->userid, $title, $content);
		redirect();
	}
	
	
	function action_create() {
		global $session, $db;
		
		// this is safe, they are quoted for debe in $db->create_case
		$name     = isset($_POST['name'])     ? htmlspecialchars($_POST['name']) : '';
		$project  = isset($_POST['project'])  ? ((int) $_POST['project'])        : NULL;
		$assigned = isset($_POST['assigned']) ? ((int) $_POST['assigned'])       : NULL;
		
		if (!$assigned) {
			// get project owner as default.
			$assigned = $db->get_project_owner($project);
		}
		
		$type     = isset($_POST['category']) ? ((int) $_POST['category'])           : NULL;
		$priority = isset($_POST['priority']) ? ((int) $_POST['priority'])           : NULL;
		$version  = isset($_POST['version'])  ? htmlspecialchars($_POST['version'])  : '';
		$computer = isset($_POST['computer']) ? htmlspecialchars($_POST['computer']) : '';
		
		$bug = $db->create_case($name, $project, $assigned, $type, $priority, $version, $computer);
		
		$message      = isset($_POST['description']) ? action_format_message($_POST['description']) : '';
		$changes      = "";
		$subscribe    = isset($_POST['subscribe'])   ? ($_POST['subscribe'] ? true : false)         : false;
		
		// Log the initial state.
		$changes .= "Problem: '{$name}'<br />";
		$touser = $db->get_user($assigned);
		$changes .= "Assigned to: {$touser->fullname}<br />";
		$pname = $db->name_project($project);
		$changes .= "Project: '$pname'<br />";
		$cname = $db->name_type($type);
		$changes .= "Category: '$cname'<br />";
		$pname = $db->name_priority($priority);
		$changes .= "Priority: '$pname'<br />";
		$changes .= "Version: '{$version}'<br />";
		$changes .= "Computer: '{$computer}'<br />";
		
		// Log comment and changes if any.
		$changes = '<p>' . $changes . '</p>';
		if ($message) $changes = $changes . $message;
		$db->log($bug, $session->userid, "created case", $changes);
		
		// Save project in session to help creating multiple bugs.
		$session->project = $project;
		
		if ($subscribe) {
			$db->subscribe($bug, $session->userid);
		}
		
		redirect_bug($bug);
	}
	
	
	function action_edit() {
		global $session, $db;
		
		$new           = new \stdClass();
		$new->name     = isset($_POST['name'])     ? htmlspecialchars($_POST['name'])              : '';
		$new->project  = isset($_POST['project'])  ? ((int) $_POST['project'])                     : NULL;
		$new->assigned = isset($_POST['assigned']) ? ((int) $_POST['assigned'])                    : NULL;
		$new->category = isset($_POST['category']) ? ((int) $_POST['category'])                    : NULL;
		$new->priority = isset($_POST['priority']) ? ((int) $_POST['priority'])                    : NULL;
		$new->status   = isset($_POST['status'])   ? ((int) $_POST['status'])                      : NULL;
		$new->version  = isset($_POST['version'])  ? htmlspecialchars($_POST['version'])           : '';
		$new->computer = isset($_POST['computer']) ? htmlspecialchars($_POST['computer'])          : '';
		$message       = isset($_POST['message'])  ? action_format_message($_POST['message'])      : '';
		$message_mail  = isset($_POST['message'])  ? action_format_message_mail($_POST['message']) : '';
		$bugid         = isset($_GET['bugid'])     ? ((int) $_GET['bugid'])                        : NULL;
		
		$bug = $db->get_bug($bugid);
		if (!$bug) view_error("Non-existent case.");
		$newuserdata = $db->get_user($new->assigned);
		if ($newuserdata->enabled == "f") view_error("Can't assign to a disabled user.");
		
		$changes      = "";
		$changes_mail = "";
		$subscribe    = isset($_POST['subscribe'])   ? ($_POST['subscribe'] ? true : false)         : false;
		
		$subscription = $db->get_subscription($bug->id, $session->userid);
		
		// Do updates as necessary, collect a log.
		if ($bug->name != $new->name) {
			$db->set_name($bug->id, $new->name);
			$changes .= "Changed problem to '{$new->name}'<br />";
			$changes_mail .= "Changed problem to '{$new->name}'\n";
		}
		if ($bug->assigned != $new->assigned) {
			$touser = $db->get_user($new->assigned);
			$db->assign_bug($bug->id, $new->assigned);
			$changes .= "Assigned to {$touser->fullname}<br />";
			$changes_mail .= "Assigned to {$touser->fullname}\n";
		}
		if ($bug->project != $new->project) {
			$db->set_project($bug->id, $new->project);
			$pname = $db->name_project($new->project);
			$changes .= "Changed project to '$pname'<br />";
			$changes_mail .= "Changed project to '$pname'\n";
		}
		if ($bug->type != $new->category) {
			$db->set_category($bug->id, $new->category);
			$cname = $db->name_type($new->category);
			$changes .= "Changed category to '$cname'<br />";
			$changes_mail .= "Changed category to '$cname'\n";
		}
		if ($bug->priority != $new->priority) {
			$db->set_priority($bug->id, $new->priority);
			$pname = $db->name_priority($new->priority);
			$changes .= "Changed priority to '$pname'<br />";
			$changes_mail .= "Changed priority to '$pname'\n";
		}
		if ($bug->status != $new->status) {
			$db->set_status($bug->id, $new->status);
			$sname = $db->name_status($new->status);
			$changes .= "Changed status to '$sname'<br />";
			$changes_mail .= "Changed status to '$sname'\n";
		}
		if ($bug->version != $new->version) {
			$db->set_version($bug->id, $new->version);
			$changes .= "Changed version to '{$new->version}'<br />";
			$changes_mail .= "Changed version to '{$new->version}'\n";
		}
		if ($bug->computer != $new->computer) {
			$db->set_computer($bug->id, $new->computer);
			$changes .= "Changed computer to '{$new->computer}'<br />";
			$changes_mail .= "Changed computer to '{$new->computer}'\n";
		}
		if ($subscribe && !$subscription) {
			$db->subscribe($bug->id, $session->userid);
		} else if (!$subscribe && $subscription) {
			$db->unsubscribe($bug->id, $session->userid);
		}
		
		// Log comment and changes if any.
		if ($changes) {
			$changes = '<p>' . $changes . '</p>';
			if ($message) {
				$changes .= $message;
				$changes_mail .= "--\n\n" . $message_mail;
			}
			
			$db->log($bug->id, $session->userid, "modified case", $changes);
			action_edit_mail($bug->id, $session->userid, "modified case", $changes_mail);
		} else if ($message) {
			$db->log($bug->id, $session->userid, "commented", $message);
			action_edit_mail($bug->id, $session->userid, "commented", $message_mail);
		}
		
		redirect_bug($bug->id);
	}
	
	
	function handle_actions($action) {
		global $session, $config;
		
		// This check is still here to protect actions, as we only
		// check for the actual page view in 'index.php'
		if(isset($session->userid) || $action == 'login') {
			switch ($action) {
				case 'login':
					action_login();
					break;
				
				case 'logout':
					session_destroy();
					break;
				
				case 'casenew':
					action_create();
					break;
				
				case 'edit':
					action_edit();
					break;
				
				case 'adduser':
					action_adduser();
					break;
				
				case 'addproject':
					action_addproject();
					break;
				
				case 'edituser':
					action_edituser();
					break;
				
				case 'editproject':
					action_editproject();
					break;
				
				case 'enable_user':
					action_enable_user();
					break;
				
				case 'disable_user':
					action_disable_user();
					break;
				
				case 'delproject':
					action_delproject();
					break;
				
				case 'password':
					action_password();
					break;
				
				case 'postnews':
					action_postnews();
				
				default:
					// FIXME: error?
			}
		}
		
		// Redirect back to main page.
		redirect();
	}
?>
