<?php
	require_once "inc/config.php";
	
	
	class DBConnection {
		var $conn;
		
		
		function __construct($connstr) {
			$this->conn = pg_connect($connstr);
		}
		
		
		function fetch_object_array($result) {
			$numrows = pg_num_rows($result);
			$array = array();
			for($i = 0; $i < $numrows; $i++) {
				$array[$i] = pg_fetch_object($result, $i);
			}
			return $array;
		}
		
		
		function login($username, $password) {
			$Pusername = pg_escape_string($username);
			// password is safe as it's md5()
			
			$result = pg_query($this->conn, <<<EOT
SELECT id, super FROM wastebug.wb_users
WHERE name = '$Pusername' AND password = '$password' AND enabled = true;
EOT
);
			if(pg_num_rows($result) == 1) {
				$row = pg_fetch_object($result, 0);
				return $row;
			} else return false;
		}
		
		
		function create_user($uname, $password, $fname, $email, $super) {
			$Puname = pg_escape_string($uname);
			// password is safe as it's md5()
			$Pfname = pg_escape_string($fname);
			$Pemail = pg_escape_string($email);
			$Psuper = $super ? 't' : 'f';
			
			pg_query($this->conn, <<<EOT
INSERT INTO wastebug.wb_users (name, password, fullname, email, super)
VALUES ('$Puname', '$password', '$Pfname', '$Pemail', '$Psuper');
EOT
);
		}
		
		
		function create_project($name, $owner) {
			$Pname = pg_escape_string($name);
			
			pg_query($this->conn, <<<EOT
INSERT INTO wastebug.wb_projects (name, owner) VALUES ('$Pname', '$owner');
EOT
);
		}
		
		
		function edit_user($uid, $uname, $fname, $email, $super) {
			$Puname = pg_escape_string($uname);
			$Pfname = pg_escape_string($fname);
			$Pemail = pg_escape_string($email);
			$Psuper = $super ? 't' : 'f';
			
			pg_query($this->conn, <<<EOT
UPDATE wastebug.wb_users SET
name = '$Puname', fullname = '$Pfname',
email = '$Pemail', super = '$Psuper' WHERE id = '$uid';
EOT
);
		}
		
		
		function set_password($uid, $password) {
			// password is safe as it's md5()
			
			pg_query($this->conn, <<<EOT
UPDATE wastebug.wb_users SET password = '$password' WHERE id = '$uid';
EOT
);
		}
		
		
		function edit_project($pid, $name, $owner) {
			$Pname = pg_escape_string($name);
			
			pg_query($this->conn, <<<EOT
UPDATE wastebug.wb_projects SET
name = '$Pname', owner = '$owner' WHERE id = '$pid';
EOT
);
		}
		
		
		function set_user_enabled($userid, $newval) {
			$value = ($newval ? "true" : "false");
			
			pg_query($this->conn, <<<EOT
UPDATE wastebug.wb_users SET enabled = $value WHERE id = '$userid';
EOT
);
		}
		
		
		function delete_project($projectid) {
			pg_query($this->conn, <<<EOT
DELETE FROM wastebug.wb_projects WHERE id = '$projectid';
EOT
);
		}
		
		
		function get_projects() {
			$result = pg_query($this->conn, <<<EOT
SELECT id, name, owner, open, closed FROM wastebug.wb_project_stats ORDER BY name;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_categories() {
			$result = pg_query($this->conn, <<<EOT
SELECT id, name FROM wastebug.wb_type ORDER BY name;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_users() {
			$result = pg_query($this->conn, <<<EOT
SELECT id, fullname as name, email, enabled FROM wastebug.wb_users ORDER BY name;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_enabled_users() {
			$result = pg_query($this->conn, <<<EOT
SELECT id, fullname as name, email FROM wastebug.wb_users
WHERE enabled = true ORDER BY name;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_priorities() {
			$result = pg_query($this->conn, <<<EOT
SELECT id, name FROM wastebug.wb_priority ORDER BY name;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_statuses() {
			$result = pg_query($this->conn, <<<EOT
SELECT id, name FROM wastebug.wb_status ORDER BY id;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function name_status($id) {
			$result = pg_query($this->conn, <<<EOT
SELECT name FROM wastebug.wb_status WHERE id = '$id';
EOT
);
			$obj = pg_fetch_object($result, 0);
			return $obj->name;
		}
		
		
		function name_type($id) {
			$result = pg_query($this->conn, <<<EOT
SELECT name FROM wastebug.wb_type WHERE id = '$id';
EOT
);
			$obj = pg_fetch_object($result, 0);
			return $obj->name;
		}
		
		
		function name_priority($id) {
			$result = pg_query($this->conn, <<<EOT
SELECT name FROM wastebug.wb_priority WHERE id = '$id';
EOT
);
			$obj = pg_fetch_object($result, 0);
			return $obj->name;
		}
		
		
		function name_project($id) {
			$result = pg_query($this->conn, <<<EOT
SELECT name FROM wastebug.wb_projects WHERE id = '$id';
EOT
);
			// We need the check anyway, let's but an 'egg' here =)
			if(pg_num_rows($result) != 1) return "Retirement-Planning for Kids - AS/400 version";
			$obj = pg_fetch_object($result, 0);
			return $obj->name;
		}
		
		
		function name_user($id) {
			$user = $this->get_user($id);
			return $user->fullname;
		}
		
		
		function create_case($name, $project, $assigned, $type, $priority, $version, $computer) {
			$Bname = pg_escape_string($name);
			$Bproject = $project;
			$Bassigned = $assigned;
			$Btype = $type;
			$Bpriority = $priority;
			$Bversion = pg_escape_string($version);
			$Bcomputer = pg_escape_string($computer);
			
			$result = pg_query($this->conn, <<<EOT
INSERT INTO wastebug.wb_bugs(name, project, assigned, type, priority, version, computer)
VALUES ('$Bname', '$Bproject', '$Bassigned', '$Btype', '$Bpriority', '$Bversion', '$Bcomputer');
EOT
);
			// Need to return the new bug id..
			$oid = pg_last_oid($result);
			$result = pg_query($this->conn, <<<EOT
SELECT id FROM wastebug.wb_bugs WHERE oid = '$oid';
EOT
);
			$object = pg_fetch_object($result, 0);
			return $object->id;
		}
		
		
		function get_bugs($where, $project) {
			if($project) $where .= " AND projectid = '$project'";
			
			$result = pg_query($this->conn, <<<EOT
SELECT id, name, project, owner, type, icon, priority, status,
date_trunc('second', opened) as opened
FROM wastebug.wb_bug_list WHERE $where ORDER BY priority, type, opened;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function users_bugs($user, $project) {
			$array = $this->get_bugs("open = true AND ownerid = '$user'", $project);
			return $array;
		}
		
		
		function open_bugs($project) {
			$array = $this->get_bugs("open = true", $project);
			return $array;
		}
		
		
		function all_bugs($project) {
			$array = $this->get_bugs("1 = 1", $project);
			return $array;
		}
		
		
		function log($bug, $user, $action, $data) {
			$text = pg_escape_string($data);
			
			pg_query($this->conn, <<<EOT
INSERT INTO wastebug.wb_log(bug, person, action, data)
VALUES ('$bug','$user','$action','$text');
EOT
);
		}
		
		
		function get_bug($bug) {
			$result = pg_query($this->conn, <<<EOT
SELECT id, name, project, assigned, type, priority, version, computer, status,
opened FROM wastebug.wb_bugs WHERE id = '$bug';
EOT
);
			if(pg_num_rows($result) != 1) return false;
			$bug = pg_fetch_object($result, 0);
			return $bug;
		}
		
		
		function get_bug_log($bug) {
			$result = pg_query($this->conn, <<<EOT
SELECT u.fullname as uname, l.action as action,
date_trunc('seconds', l.date) as date, l.data as message
FROM wastebug.wb_log l, wastebug.wb_users u
WHERE l.bug = '$bug' AND l.person = u.id ORDER BY date;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_subscription($bug, $user) {
			$result = pg_query($this->conn, <<<EOT
SELECT * FROM wastebug.wb_subscriptions WHERE bugid = '$bug' AND userid = '$user';
EOT
);
			if(pg_num_rows($result) > 0) return true;
			else return false;
		}
		
		
		function subscribe($bug, $user) {
			$result = pg_query($this->conn, <<<EOT
INSERT INTO wastebug.wb_subscriptions (bugid, userid) VALUES ('$bug', '$user');
EOT
);
		}
		
		
		function unsubscribe($bug, $user) {
			$result = pg_query($this->conn, <<<EOT
DELETE FROM wastebug.wb_subscriptions WHERE bugid = '$bug' AND userid = '$user';
EOT
);
		}
		
		
		function post_news($user, $title, $content) {
			$title = pg_escape_string($title);
			$content = pg_escape_string($content);
			
			$result = pg_query($this->conn, <<<EOT
INSERT INTO wastebug.wb_news (userid, title, content)
VALUES ('$user', '$title', '$content');
EOT
);
		}
		
		
		function get_news($limit) {
			if($limit) $limit = "LIMIT $limit";
			
			$result = pg_query($this->conn, <<<EOT
SELECT
	u.fullname as user,
	n.title as title,
	date_trunc('seconds', n.posted) as posted,
	n.content as content
FROM wastebug.wb_news n, wastebug.wb_users u
WHERE u.id = n.userid
ORDER BY posted DESC $limit;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_subscriptions($bug) {
			$result = pg_query($this->conn, <<<EOT
SELECT u.email as email FROM wastebug.wb_subscriptions s, wastebug.wb_users u
WHERE s.bugid = '$bug' AND s.userid = u.id AND u.enabled = true;
EOT
);
			$array = $this->fetch_object_array($result);
			return $array;
		}
		
		
		function get_user($user) {
			$result = pg_query($this->conn, <<<EOT
SELECT id, fullname, email, name, super, enabled
FROM wastebug.wb_users WHERE id = '$user';
EOT
);
			$user = pg_fetch_object($result, 0);
			return $user;
		}
		
		
		function has_projects($user) {
			$result = pg_query($this->conn, <<<EOT
SELECT id FROM wastebug.wb_projects WHERE owner = '$user';
EOT
);
			if(pg_num_rows($result) != 0) return true;
			else return false;
		}
		
		
		function get_project($pid) {
			$result = pg_query($this->conn, <<<EOT
SELECT id, name, owner FROM wastebug.wb_projects WHERE id = '$pid';
EOT
);
			$project = pg_fetch_object($result, 0);
			return $project;
		}
		
		
		function get_project_owner($project) {
			$result = pg_query($this->conn, <<<EOT
SELECT owner FROM wastebug.wb_projects WHERE id = '$project';
EOT
);
			$row = pg_fetch_object($result, 0);
			$owner = $row->owner;
			return $owner;
		}
		
		
		function update_bug($bug, $set) {
			pg_query($this->conn, <<<EOT
UPDATE wastebug.wb_bugs SET $set WHERE id = '$bug';
EOT
);
		}
		
		
		function assign_bug($bug, $value) {
			$this->update_bug($bug, "assigned = '$value'");
		}
		
		
		function set_name($bug, $value) {
			$value = pg_escape_string($value);
			$this->update_bug($bug, "name = '$value'");
		}
		
		
		function set_priority($bug, $value) {
			$this->update_bug($bug, "priority = '$value'");
		}
		
		
		function set_project($bug, $value) {
			$this->update_bug($bug, "project = '$value'");
		}
		
		
		function set_category($bug, $value) {
			$this->update_bug($bug, "type = '$value'");
		}
		
		
		function set_status($bug, $value) {
			$this->update_bug($bug, "status = '$value'");
		}
		
		
		function set_version($bug, $value) {
			$value = pg_escape_string($value);
			$this->update_bug($bug, "version = '$value'");
		}
		
		
		function set_computer($bug, $value) {
			$value = pg_escape_string($value);
			$this->update_bug($bug, "computer = '$value'");
		}
	}
	
	
	$db = new DBConnection($config['connstr']);
?>
