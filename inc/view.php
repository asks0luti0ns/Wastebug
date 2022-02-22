<?php
	require_once "inc/config.php";
	require_once "inc/database.php";

	function view_login() {
		global $config;
		?>
<form method="post" action="<?php print $config['path'] ?>?action=login">
<table>
<tr>
	<td>Username:</td>
	<td><input type="text" name="username" size="20" maxlength="20" /></td>
</tr><tr>
	<td>Password:</td>
	<td><input type="password" name="password" size="20" maxlength="20" /></td>
</tr><tr>
	<td /><td><input type="submit" value="Login" /></td>
</tr>
</table>
</form>
		<?php
	}

	function view_title($title) {
		print "<h2>$title</h2>";
	}

	// we take array of objects
	// each object must have 'id' and 'name'
	function view_options($name, $options, $selected = 0, $script = 0) {
		global $config;
		print <<<EOT
<select name="$name" style="width:{$config['width']}px;"
EOT;
		if($script) print " $script";
		print ">";
		foreach ($options as $o) {
			print "<option value=\"{$o->id}\"";
			if($o->id == $selected) print ' selected="selected"';
			print ">{$o->name}</option>";
		}
		print "</select>";
	}

	function view_input($name, $value = 0) {
		global $config;
		print <<<EOT
<input name="$name" size="50" maxlength="100" style="width:{$config['width']}px;"
EOT;
		if($value) print " value=\"$value\"";
		print '/>';
	}

	function view_textarea($name) {
		// Have width be 100%
		// Works as long as it's kept inside a table
		// with other inputs that have explicit width
		print <<<EOT
<textarea name="$name" style="width:100%; height:150px;"
	rows="10" cols="50"></textarea>
EOT;
	}
	
	function view_projects() {
		global $db, $session;
		view_title("Projects");

		$projects = $db->get_projects();

		foreach ($projects as $p) {
			$open = (int) $p->open;
			$closed = (int) $p->closed;
			$total = $open + $closed;
			if($total) $percent = (int) (($closed * 100) / $total);
			else $percent = 100;
			print "<h3>{$p->name}:</h3>";
			print "<table>";
			print "<tr><td>Owner:</td><td>{$p->owner}</td></tr>";
			print "<tr><td>Open cases:</td><td>{$open}</td></tr>";
			print "<tr><td>Closed cases:</td><td>{$closed}</td></tr>";
			print "<tr><td>Total:</td>"
			 . "<td>{$total} (with {$percent} % solved)</td></tr>";
			print "</table>";
		}
	}

	function view_admin() {
		global $session, $db;
		if(!$session->super) return;
		
		$projects = $db->get_projects();
		$users = $db->get_users();

		view_title("Administration:");

		print "<h3>Projects:</h3>";
		
		print '<table class="admin">';
		print '<th>Project:</th><th>Owner:</th>';
		foreach($projects as $p) {
			print '<tr>';
			print "<td>{$p->name}</td>";
			print "<td>{$p->owner}</td>";
			print "<td><a href=\"{$config['path']}?"
                                . "view=editproject&pid={$p->id}"
                                . "\">edit</a></td>";
			print "<td><a href=\"{$config['path']}?"
				. "view=confirm&type=p&delid={$p->id}"
				. "\">delete</a></td>";
			print "</tr>\n";
		}
		print  "<tr><td style=\"text-align:right;\" colspan=\"4\">"
			. "<a href=\"{$config['path']}?view=addproject\">"
			. "create new project</a></td></tr>\n";
		print "</table>\n";
		print "<h3>Users:</h3>";
		
		print '<table class="admin">';
		print '<th>User:</th><th>Email:</th>';
		foreach($users as $u) {
			print $u->enabled == "t" ? '<tr>' : '<tr class="disabled">';
			print "<td>{$u->name}</td>";
			print "<td>{$u->email}</td>";
			print "<td><a href=\"{$config['path']}?"
                                . "view=edituser&uid={$u->id}"
                                . "\">edit</a></td>";
                        print "<td><a href=\"{$config['path']}?"
                                . "action=" . ($u->enabled == "t"
                                                ? "disable_user"
                                                : "enable_user")
                                . "&uid={$u->id}\">" . ($u->enabled == "t"
                                                        ? "disable"
                                                        : "enable")
                                . "</a></td>";
			print "</tr>\n";
		}
		print  "<tr><td style=\"text-align:right;\" colspan=\"4\">"
			. "<a href=\"{$config['path']}?view=adduser\">"
			. "create new user</a></td></tr>\n";
		print "</table>\n";
	}

	function view_confirm() {
		global $session, $db;
		if(!$session->super) return;

		$type = $_GET['type'];
		$id = (int) $_GET['delid'];
		
		switch($type) {
			case 'p':
				$what = "project '";
				$what .= $db->name_project($id);
				$what .= "'";
				$action = 'delproject';
				break;
			default:
				print "<p>Bogus d-type.</p>";
				return;
		}

		view_title("Confirm delete:");

		print "<h4>You are about to delete $what.</h4>\n";
		print "<p><a href=\"{$config['path']}?action=$action&delid=$id\">";
		print "Delete!</a> <a href=\"{$config['path']}?view=admin\">";
		print "Cancel</a></p>";
	}

	function view_addproject() {
		global $session, $db;
		if(!$session->super) return;

		view_title("Create new project:");

		print "<form action=\"{$config['path']}?action=addproject\" "
			. "method=\"post\"><table>";

		print "<tr><td>Name:</td><td>";
		print view_input("name");
		print "</td></tr>\n";
		
		print "<tr><td>Owner:</td><td>";
		print view_options("owner", $db->get_enabled_users(),
                        $session->userid);
		print "</td></tr>\n";

		print '<tr><td /><td style="text-align: right;">'
			.'<input type="submit" value="Create" />'
			."</td></tr>\n";
		
		print "</table></form>\n";

	}

	// take stuff require for retry as arguments.
	function view_adduser () {
		global $session, $db, $adduser_retry;
		if(!$session->super) return;

		view_title("Create new user:");
		
		print "<form action=\"{$config['path']}?action=adduser\" "
			. "method=\"post\"><table><tr>";
		
		?>
	<td>Username:</td>
	<td>
<input type="text" name="name" size="20" maxlength="20" value="" /></td>
</tr><tr>
	<td>Password:</td>
	<td>
<input type="password" name="pass1" size="20" maxlength="20" value="" /></td>
</tr><tr>
	<td>Password again:</td>
	<td>
<input type="password" name="pass2" size="20" maxlength="20" value="" /></td>
</tr><tr>
		<?php
		print "<tr><td>Full name:</td><td>";
		print view_input("fullname");
		print "</td></tr>\n";
		
		print "<tr><td>Email address:</td><td>";
		print view_input("email");
		print "</td></tr>\n";

		print "<tr><td /><td>"
			.'<input type="checkbox" name="super">'
			.'Administrator</input>'
			."</td></tr>\n";

		print '<tr><td /><td style="text-align: right;">'
			.'<input type="submit" value="Create" />'
			."</td></tr>\n";
		
		print "</table></form>\n";
	}
        
	function view_editproject() {
		global $session, $db;
		if(!$session->super) return;

		view_title("Edit project:");

                $project = $db->get_project((int) $_GET['pid']);

		print "<form action=\"{$config['path']}?action=editproject\" "
			. "method=\"post\">"
                        . "<input type=\"hidden\" name=\"pid\" "
                        . "value=\"{$project->id}\"/><table>";

		print "<tr><td>Name:</td><td>";
		print view_input("name", $project->name);
		print "</td></tr>\n";
		
		print "<tr><td>Owner:</td><td>";
		print view_options("owner", $db->get_enabled_users(),
                        (int)$project->owner);
		print "</td></tr>\n";
                
		print '<tr><td /><td style="text-align: right;">'
			.'<input type="submit" value="Save" /> '
                        .'<input type="reset" value="Reset" />'
			."</td></tr>\n";
		
		print "</table></form>\n";

	}

	// take stuff require for retry as arguments.
	function view_edituser () {
		global $session, $db;
		if(!$session->super) return;

		view_title("Edit user:");

                $user = $db->get_user((int) $_GET['uid']);
		
                print ($user->enabled == "t"
                        ? ""
                        : "<p><i>This account is currently disabled.</i></p>");
		print "<form action=\"{$config['path']}?action=edituser\" "
			. "method=\"post\">"
                        . "<input type=\"hidden\" name=\"uid\" "
                        . "value=\"{$user->id}\" /><table><tr>";
		
		?>
	<td>Username:</td>
	<td>
<input type="text" name="name" size="20" maxlength="20" value="<?php
        echo $user->name;
        ?>" /></td>
</tr><tr>
	<td>Password:</td>
	<td>
<input type="password" name="pass1" size="20" maxlength="20" value="" />
<i>Leave empty to keep unchanged.</i></td>
</tr><tr>
	<td>Password again:</td>
	<td>
<input type="password" name="pass2" size="20" maxlength="20" value="" /></td>
</tr><tr>
		<?php
		print "<tr><td>Full name:</td><td>";
		print view_input("fullname", $user->fullname);
		print "</td></tr>\n";
		
		print "<tr><td>Email address:</td><td>";
		print view_input("email", $user->email);
		print "</td></tr>\n";

		print "<tr><td /><td>"
			.'<input type="checkbox" name="super"'
                        . ($user->super == "t" ? " checked=\"checked\"" : "")
			.'>Administrator</input>'
			."</td></tr>\n";
                        

		print '<tr><td /><td style="text-align: right;">'
			.'<input type="submit" value="Save" /> '
                        .'<input type="reset" value="Reset" />'
			."</td></tr>\n";
		
		print "</table></form>\n";
	}

        function view_password() {
                global $session;
                
                view_title("Change password:");
		
		print "<form action=\"{$config['path']}?action=password\" "
			. "method=\"post\"><table><tr>";
		
		?>
	<td>New password:</td>
	<td>
<input type="password" name="pass1" size="20" maxlength="20" value="" /></td>
</tr><tr>
	<td>New password again:</td>
	<td>
<input type="password" name="pass2" size="20" maxlength="20" value="" /></td>
</tr><tr><td><br /></td></tr><tr>
	<td>Old password:</td>
	<td>
<input type="password" name="opass" size="20" maxlength="20" value="" /></td>
</tr><tr>
		<?php

		print '<tr><td /><td style="text-align: right;">'
			.'<input type="submit" value="Save" /> '
			."</td></tr>\n";
		
		print "</table></form>\n";
                
        }
	
	function view_list($listtype) {
		global $db, $session, $config;

                if($_GET['short']) {
                        $short = $_GET['short'];
                        if($short != "false") $session->shortlist = true;
                        else $session->shortlist = false;
                }

                // is this a short form?
                $short = $session->shortlist;

		switch($listtype) {
			case 'my':
				$bugs = $db->users_bugs($session->userid,
							$session->project);
				view_title("My cases:");
				break;
			case 'open':
				$bugs = $db->open_bugs($session->project);
				view_title("Open cases:");
				break;
			case 'all':
				$bugs = $db->all_bugs($session->project);
				view_title("All cases:");
				break;
			default:
				print "<p>Bogus listtype: '$listtype'</p>";
				return;
		}
		
		if($session->project) {
			$pf = "Only showing cases in '"
				. $db->name_project($session->project) . "':";
		} else {
			$pf = "Showing cases in all projects:";
		}

		print '<form method="get" action="'
			. $config['path'] . '"><p>Filter by project: ';
		// Numbering in from $db->get_projects starts from 1
		// Array indexing starts from 0 though
		$projects[-1]->id = 0;
		$projects[-1]->name = 'Any project';
		$projects += $db->get_projects();
		print '<input type="hidden" name="view" value="list" />';
		print '<input type="hidden" name="type" value="'.$listtype.'" />';
		view_options("project", $projects, $session->project,
				'onchange="this.form.submit();"');
		// autosubmits on change, have button for no-script browsers
		// Notice that this violates XHTML 1.0 (strict and transitional)
		// since <noscript> is only allowed in block context.
                //
                // Anyway, Links seems to NOT display stuff from <noscript> even
                // if it does not support JavaScript. Having a <script> block
                // also doesn't help so it does not follow the standard.
                if(preg_match("/Links/", $_SERVER['HTTP_USER_AGENT']))
                        print '<input type="submit" value="Set" />';
                else print '<noscript>'
                        . '<input type="submit" value="Set" /></noscript>';
		print '</p></form>';
                print "<p>Switch to <a href=\"{$config['path']}?"
                        . "view=list&type=$listtype&project={$session->project}"
                        . "&short=" . ($short ? "false" : "true")
                        . '">'
                        . ($short ? "long" : "short")
                        . ' form</a>.</p>';

		print "<h3>$pf</h3>";
		if($bugs) {
                        if(!$short) print '<table class="buglist">';
                        else print <<<EOT
<table class="buglistshort">
<tr><th/>
        <th>Name</th>
        <th>Priority</th>
        <th>Status</th>
        <th>Assigned to</th>
        <th>Project</th>
        <th>Opened</th>
</tr>
EOT;
			foreach ($bugs as $b) {
				$class = preg_replace("/[^a-z]/", "",
					strtolower($b->status));
				$critical = (substr($b->priority,0,1) == '1')
					? ' class="critical"' : '';
                                if ($short) {
                                        if(strlen($b->name) > 40)
                                                $b->name =
                                                        substr($b->name, 0, 38)
                                                        . "...";
                                        print <<<EOT
<tr class="$class"><td><img class="smallicon" src="{$b->icon}"
        alt="{$b->type}"/>
</td><td><a href="{$config['path']}?view=bug&amp;bugid={$b->id}"
        >#{$b->id}: {$b->name}</a></td>
        <td$critical>{$b->priority}</td>
        <td>{$b->status}</td>
        <td>{$b->owner}</td>
        <td>{$b->project}</td>
        <td>{$b->opened}</td>
</tr>
EOT;
                                } else print <<<EOT
<tr><td><div class="bug"><h4 class="$class">
<a href="{$config['path']}?view=bug&amp;bugid={$b->id}"
	>#{$b->id}: {$b->name}</a></h4>
<p><img class="icon" src="{$b->icon}" alt="" />
<b>{$b->type}</b> with
priority <b$critical>{$b->priority}</b>,
opened {$b->opened}.
<br />
Status is <b>{$b->status}</b>,
project <b>{$b->project}</b>,
assigned to <b>{$b->owner}</b>.
</p></div></td></tr>

EOT;
			}
			print '</table>';
		} else print '<h4>No cases.</h4>';
	}

	function view_bug() {
		global $session, $db, $config;
		
		$bugid = (int) $_GET['bugid'];
		$bug = $db->get_bug($bugid);

		if(!$bug) {
			view_title("Error:");
			print "<p>No such case: #{$bugid}</p>";
			return;
		}
		
		$log = $db->get_bug_log($bugid);

                $subscription = $db->get_subscription($bugid, $session->userid);

		print '<p><a href="#bottom" id="top">jump to bottom</a></p>';

		view_title ("Case #{$bug->id}:");
		
		?>
<form method="post" action="<?php
	print $config['path']; ?>?action=edit&amp;bugid=<?php
	print $bug->id; ?>">
<table>
	<tr><td>Problem:</td>
		<td><?php view_input("name", $bug->name); ?></td></tr>
	<tr><td>Project:</td>
		<td><?php 
			view_options("project", $db->get_projects(),
				$bug->project);
		?></td></tr>
	<tr><td>Assigned to:</td>
		<td><?php
			view_options("assigned", $db->get_enabled_users(),
				$bug->assigned);
		?></td></tr>
	<tr><td>Category:</td>
		<td><?php
			view_options("category", $db->get_categories(),
				$bug->type);
		?></td></tr>
	<tr><td>Priority:</td>
		<td><?php
			view_options("priority", $db->get_priorities(),
				$bug->priority);
		?></td></tr>
	<tr><td>Version:</td>
		<td><?php view_input("version", $bug->version); ?></td></tr>
	<tr><td>Computer:</td>
		<td><?php view_input("computer", $bug->computer); ?></td></tr>
        <tr><td></td><td><input type="checkbox" name="subscribe" <?php
                print ($subscription ? "checked=\"chechked\"" : "");
        ?>>Send me email notifications.</input></td></tr>
	<tr><td>Log message:</td><td /></tr>
	<tr><td colspan="2"><?php view_textarea("message"); ?></td></tr>
	<tr><td>Status:</td>
		<td><?php
			view_options("status", $db->get_statuses(),
				$bug->status);
		?></td></tr>
	<tr><td /><td style="text-align:right;">
		<input type="submit" value="Save" />
		<input type="reset" value="Reset" />
		</td></tr>
</table>
<?php
		if($log)
		foreach($log as $l) {
			print "<h4>{$l->uname} {$l->action} on {$l->date}:</h4>";
			print $l->message;
		}
	?>
</form>
		<p><a href="#top" id="bottom">jump to top</a></p>
<?php
	}

	function view_create() {
		global $db, $config, $session;

                $projects = $db->get_projects();

                if(count($projects) == 0) {
                        ?>
        <p>Please ask your administrator to create at least one project first.</p>
        <?php
                        return;
                }

		view_title("Create new case:");

		?>
<form method="post" action="<?php print $config['path']; ?>?action=casenew">
<table>
	<tr><td>Problem:</td>
		<td><?php view_input("name"); ?></td></tr>
	<tr><td>Project:</td>
		<td><?php 
			// make project default to current filter if any
			view_options("project", $db->get_projects(),
				$session->project);
		?></td></tr>
	<tr><td>Assigned to:</td>
		<td><?php
			// make assigned default to project owner
			$users[-1]->id = 0;
			$users[-1]->name = 'Project Owner';
			$users += $db->get_enabled_users();
			view_options("assigned", $users, 0);
		?></td></tr>
	<tr><td>Category:</td>
		<td><?php
			view_options("category", $db->get_categories());
		?></td></tr>
	<tr><td>Priority:</td>
		<td><?php
			// golden middle road..
			view_options("priority", $db->get_priorities(), 3);
		?></td></tr>
	<tr><td>Version:</td>
		<td><?php view_input("version"); ?></td></tr>
	<tr><td>Computer:</td>
		<td><?php view_input("computer"); ?></td></tr>
	<tr><td></td><td><input type="checkbox" name="subscribe"
                >Send me email notifications.</input></td></tr>
	<tr><td>Description:</td><td /></tr>
	<tr><td colspan="2"><?php view_textarea("description"); ?></td></tr>
	<tr><td /><td style="text-align:right">
		<input type="submit" value="Create" />
		<input type="reset" value="Clear" /></td></tr>
</table>
</form>
<?php
		print '<h3>Bug-Reporting Checklist:</h3>';
		print $config['checklist'];
	}

        function view_news($limit) {
                global $db;

                $news = $db->get_news($limit);
                foreach($news as $n) {
                        print "\n<p><b>{$n->title}</b><br/>";
                        print "{$n->user} on {$n->posted}</p>\n";
                        print $n->content;
                }
        }

        function view_allnews() {
                view_title("News archive");
                view_news(false);
        }

	function view_default() {
		global $session, $config;
                print "<p>";
		if($session->super) {
			print "You are an "
				. "<a href=\"{$config['path']}?view=admin\">"
				. "administrator</a>. "
                                . "You can <a href=\"{$config['path']}?"
                                . "view=postnews\">post news</a>.<br />";
		}
                print "Want to change your <a href=\""
                        . "{$config['path']}?view=password\">password</a>?</p>";
		view_title("News");
                view_news($config['newslimit']);
                print "<p>Show <a href=\"{$config['path']}?view=allnews\">"
                        . "all news</a>.</p>";
	}

        function view_postnews() {
                global $session, $config;
                if(!$session->super) return;

                view_title("Post news:");

                ?>
<form method="post" action="<?php print $config['path'] ?>?action=postnews">
<table>
        <tr><td>Title:</td><td><?php view_input("title"); ?></td></tr>
        <tr><td>Content:</td><td/></tr>
        <tr><td colspan="2"><?php view_textarea("content"); ?></td></tr>
        <tr><td /><td style="text-align:right">
                <input type="submit" value="Post" /></td></tr>
</table>
</form>

                <?php
        }

	function handle_view($view) {
		global $session, $config;

		// This is really relevant only to view_list(),
		// but we have to do it here so we get the links right.
		if(isset($_GET['project']))
			$session->project = (int) $_GET['project'];

		if($view != 'login') {
			// logged in header..
			$viewlist = "view=list&amp;project={$session->project}";
			print <<<EOT
<form method="get" action="{$config['path']}">
<p class="mainbar">
Logged in as {$session->username}:
<a href="{$config['path']}?view=create">New!</a>
<a href="{$config['path']}?$viewlist&amp;type=my">My cases</a>
<a href="{$config['path']}?$viewlist&amp;type=open">List</a>
<a href="{$config['path']}?$viewlist&amp;type=all">Archive</a>
<a href="{$config['path']}?view=projects">Projects</a>
&nbsp;&nbsp;&nbsp;
jump to case #:
<input type="hidden" name="view" value="bug" />
<input type="text" size="5" style="font-size:8pt;" name="bugid" value="" />
&nbsp;&nbsp;&nbsp;
<a href="{$config['path']}?view=help">Help?</a>
<a href="{$config['path']}?action=logout">Logout!</a>
</p>
</form>
<table class="layout"><tr><td>
EOT;
		}
		
		switch($view) {
			case 'login':
				view_login();
				break;

			case 'list':
				view_list($_GET['type']);
				break;

			case 'projects':
				view_projects();
				break;

			case 'create':
				view_create();
				break;

			case 'help':
				include "inc/help.php";
				break;

			case 'bug':
				view_bug();
				break;

			case 'admin':
				view_admin();
				break;

			case 'confirm':
				view_confirm();
				break;

			case 'adduser':
				view_adduser();
				break;
				
			case 'addproject':
				view_addproject();
				break;

                        case 'edituser':
                                view_edituser();
                                break;

                        case 'editproject':
                                view_editproject();
                                break;

                        case 'password':
                                view_password();
                                break;

                        case 'postnews':
                                view_postnews();
                                break;

                        case 'allnews':
                                view_allnews();
                                break;

			case 'default':
				view_default();
				break;

			default:
				print "<p>Bogus view: '$view'.</p>";
		}
                print "</td></tr></table>";
	}
?>
