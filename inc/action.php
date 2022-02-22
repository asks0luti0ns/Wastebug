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
                if(!$message) return false;
                if(preg_match('/^\s*$/', $message)) return false;
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
                if(!$message) return false;
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
                        mail($u->email,
                                "[Wastebug] Case $bug: $bugname",
                                $message,
                                "Reply-to: {$config['email']}\n"
                                . "Content-type: text/plain; charset=utf-8");
                }

        }

	function action_login() {
		global $session, $db;
		$loginname = htmlspecialchars($_POST['username']);
		$user = $db->login($loginname,md5($_POST['password']));
		
		if($user) {
			$session->userid = $user->id;
			$session->super = $user->super == 't' ? true : false;
			$session->username = $_POST['username'];
		}
	}

	function action_adduser() {
		global $session, $db;

		if(!$session->super) redirect();
		
		$loginname = htmlspecialchars($_POST['name']);
		$pass1 = $_POST['pass1'];
		$pass2 = $_POST['pass2'];
		$fullname = htmlspecialchars($_POST['fullname']);
		$email = htmlspecialchars($_POST['email']);
		$super = $_POST['super'] ? true : false;

		if($pass1 != $pass2) view_error("Passwords didn't match.");
		if(!$pass1) view_error("Empty password.");
		if(!$loginname) view_error("Empty username.");
		
		$db->create_user
			($loginname, md5($pass1), $fullname, $email, $super);
		redirect("view=admin");
	}

	function action_addproject() {
		global $session, $db;

		if(!$session->super) redirect();

		$name = htmlspecialchars($_POST['name']);
		$owner = (int) $_POST['owner'];

		if(!$name) view_error("Empty project name.");

		$db->create_project($name, $owner);
		redirect("view=admin");
	}
        
	function action_edituser() {
		global $session, $db;

		if(!$session->super) redirect();
		
                $uid = (int) $_POST['uid'];
		$loginname = htmlspecialchars($_POST['name']);
		$pass1 = $_POST['pass1'];
		$pass2 = $_POST['pass2'];
		$fullname = htmlspecialchars($_POST['fullname']);
		$email = htmlspecialchars($_POST['email']);
		$super = $_POST['super'] ? true : false;

		if($pass1 != $pass2) view_error("Passwords didn't match.");
		if(!$loginname) view_error("Empty username.");
		if($pass1) $db->set_password($uid, md5($pass1));
		
		$db->edit_user
			($uid, $loginname, $fullname, $email, $super);
		redirect("view=admin");
	}

	function action_editproject() {
		global $session, $db;

		if(!$session->super) redirect();

                $pid = (int) $_POST['pid'];
		$name = htmlspecialchars($_POST['name']);
		$owner = (int) $_POST['owner'];

		if(!$name) view_error("Empty project name.");
                $newuserdata = $db->get_user($owner);
                if($newuserdata->enabled == "f")
                        view_error("Can't make disabled user project owner.");

		$db->edit_project($pid, $name, $owner);
		redirect("view=admin");
	}
	
        function action_enable_user() {
                global $session, $db;

                if(!$session->super) redirect();
                
                $user = (int) $_GET['uid'];
                $db->set_user_enabled($user, true);
                
                redirect("view=admin");
        }
        
        function action_disable_user() {
                global $session, $db;

                if(!$session->super) redirect();
                
                $user = (int) $_GET['uid'];

                $userbugs = $db->users_bugs($user, 0);
                if(count($userbugs) != 0)
                        view_error("Can't disable user with cases assigned.");

                if($db->has_projects($user))
                        view_error("Can't disable user who owns projects.");

                if($user == $session->userid)
                        view_error("You can't disable yourself.");

                $db->set_user_enabled($user, false);

                redirect("view=admin");
        }

	function action_delproject() {
		global $session, $db;

		if(!$session->super) redirect();

		$projectid = (int) $_GET['delid'];
		$db->delete_project($projectid);

		redirect("view=admin");
	}
        
	function action_password() {
		global $session, $db;

		$pass1 = $_POST['pass1'];
		$pass2 = $_POST['pass2'];
                $opass = $_POST['opass'];

                if(!$db->login($session->username, md5($opass))) {
                        view_error("Invalid (old) password.");
                }
                
		if($pass1 != $pass2) view_error("Passwords didn't match.");
		if(strlen($pass1) < 4) view_error("Password too short.");
		
                $db->set_password($session->userid, md5($pass1));
	}

        function action_postnews() {
                global $session, $db;

                if(!$session->super) redirect();

                $title = htmlspecialchars($_POST['title']);
                $content = $_POST['content'];

                $content = action_format_message($content);

                $db->post_news($session->userid, $title, $content);

                redirect();
        }

	function action_create() {
		global $session, $db;

		// this is safe, they are quoted for debe in $db->create_case
		$name = htmlspecialchars($_POST['name']);
		$project = (int) $_POST['project'];
		$assigned = (int) $_POST['assigned'];

		if(!$assigned) {
			// get project owner as default.
			$assigned = $db->get_project_owner($project);
		}
		
		$type = (int) $_POST['category'];
		$priority = (int) $_POST['priority'];
		$version = htmlspecialchars($_POST['version']);
		$computer = htmlspecialchars($_POST['computer']);
		
		$bug = $db->create_case($name, $project, $assigned,
				$type, $priority, $version, $computer);

		$message = action_format_message($_POST['description']);

		$changes = "";
                
                if($_POST['subscribe']) $subscribe = true;
                else $subscribe = false;

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
		if($message) $changes = $changes . $message;
		$db->log($bug, $session->userid, "created case", $changes);

		// Save project in session to help creating multiple bugs.
		$session->project = $project;
                
                if($subscribe) {
                        $db->subscribe($bug, $session->userid);
                }

		redirect_bug($bug);
	}

	function action_edit() {
		global $session, $db;
		$new->name = htmlspecialchars($_POST['name']);
		$new->project = (int) $_POST['project'];
		$new->assigned = (int) $_POST['assigned'];
		$new->category = (int) $_POST['category'];
		$new->priority = (int) $_POST['priority'];
		$new->status = (int) $_POST['status'];
		$new->version = htmlspecialchars($_POST['version']);
		$new->computer = htmlspecialchars($_POST['computer']);
	
		$message = action_format_message($_POST['message']);
                $message_mail = action_format_message_mail($_POST['message']);

		$bug = $db->get_bug((int) $_GET['bugid']);
                if(!$bug) view_error("Non-existent case.");
                $newuserdata = $db->get_user($new->assigned);
                if($newuserdata->enabled == "f")
                        view_error("Can't assign to a disabled user.");

		$changes = "";
                $changes_mail = "";
                
                if($_POST['subscribe']) $subscribe = true;
                else $subscribe = false;
                
                $subscription = $db->get_subscription($bug->id, $session->userid);

		// Do updates as necessary, collect a log.
		if($bug->name != $new->name) {
			$db->set_name($bug->id, $new->name);
			$changes .= "Changed problem to '{$new->name}'<br />";
			$changes_mail .= "Changed problem to '{$new->name}'\n";
		}
		if($bug->assigned != $new->assigned) {
			$touser = $db->get_user($new->assigned);
			$db->assign_bug($bug->id, $new->assigned);
			$changes .= "Assigned to {$touser->fullname}<br />";
			$changes_mail .= "Assigned to {$touser->fullname}\n";
		}
		if($bug->project != $new->project) {
			$db->set_project($bug->id, $new->project);
			$pname = $db->name_project($new->project);
			$changes .= "Changed project to '$pname'<br />";
			$changes_mail .= "Changed project to '$pname'\n";
		}
		if($bug->type != $new->category) {
			$db->set_category($bug->id, $new->category);
			$cname = $db->name_type($new->category);
			$changes .= "Changed category to '$cname'<br />";
			$changes_mail .= "Changed category to '$cname'\n";
		}
		if($bug->priority != $new->priority) {
			$db->set_priority($bug->id, $new->priority);
			$pname = $db->name_priority($new->priority);
			$changes .= "Changed priority to '$pname'<br />";
			$changes_mail .= "Changed priority to '$pname'\n";
		}
		if($bug->status != $new->status) {
			$db->set_status($bug->id, $new->status);
			$sname = $db->name_status($new->status);
			$changes .= "Changed status to '$sname'<br />";
			$changes_mail .= "Changed status to '$sname'\n";
		}
		if($bug->version != $new->version) {
			$db->set_version($bug->id, $new->version);
			$changes .= "Changed version to '{$new->version}'<br />";
			$changes_mail .= "Changed version to '{$new->version}'\n";
		}
		if($bug->computer != $new->computer) {
			$db->set_computer($bug->id, $new->computer);
			$changes .= "Changed computer to '{$new->computer}'<br />";
			$changes_mail .= "Changed computer to '{$new->computer}'\n";
		}
                
                if($subscribe && !$subscription) {
                        $db->subscribe($bug->id, $session->userid);
                } else if(!$subscribe && $subscription) {
                        $db->unsubscribe($bug->id, $session->userid);
                }

		// Log comment and changes if any.
		if($changes) {
			$changes = '<p>' . $changes . '</p>';
			if($message) {
                                $changes .= $message;
                                $changes_mail .= "--\n\n" . $message_mail;
                        }
                                
			$db->log($bug->id, $session->userid, "modified case",
				$changes);
                        action_edit_mail($bug->id, $session->userid, "modified case",
                                $changes_mail);

		} else if($message) {
			$db->log($bug->id, $session->userid, "commented",
				$message);
                        action_edit_mail($bug->id, $session->userid, "commented",
                                $message_mail);
		}

		redirect_bug($bug->id);
	}

	function handle_actions($action) {
		global $session, $config;

		// This check is still here to protect actions, as we only
		// check for the actual page view in 'index.php'
		if($session->userid || $action == 'login')
		switch($action) {
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

		// Redirect back to main page.
		redirect();
	}
?>
