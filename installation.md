---
layout: page
title: Installation
permalink: /installation/
---

0. You need a web server, PHP4 and PostgreSQL 7.3 (or newer).
	PHP 4.3 was used for development, PHP5 has not been tested.
	PHP settings needed:  
	- Sessions with cookies enabled. Auto-start is not needed.  
	- NO magic_quotes, neither for incoming data or SQL.  
	- NO register_globals (would enable risks serious security issues)

	We send email using the mail(). This usually works on Unix automatically,
	but if it doesn't, then see: http://www.php.net/manual/en/ref.mail.php

	Wastebug should work with safe-mode enabled, but this is not tested.
	Wastebug does not use any features that default safe-mode affects.
	 
	PostgreSQL 7.3 was used in development. Older versions won't work.
	Newer versions should work fine.

1. Copy "index.php", "wastebug.css", "wastebug.png" "inc/" and "pics/"
	somewhere your WWW server can find them.

	There is no need for direct access to "inc/" by the WWW users, but PHP
	needs to be able to include the files. There is a ".htaccess" file for
	Apache included. For other servers you can use other methods.

2. Replace values in "inc/config.php" with correct values.
	You need to change at least: connstr, admin, server, email, path
	You can change other values if you don't like the defaults.

3. There is a script "database.sql" which creates the database schema.
	It creates a schema called "wastebug" so you can use the same database
	for Wastebug and other stuff.

	By default, "database.sql" grants necessary permissions to database user
	called "apache". If you connect using the user who owns the database, you
	don't need to grant any permissions. In this case you can simply remove
	those commands. You can do this on Unix with:

		grep -v 'apache' database.sql > local.sql
 
	If you want the GRANTs done, but want to change the username to something
	else, it is easiest done on Unix with:

         sed -e 's/apache/wwwuser/' database.sql > local.sql

	You can then create the database with:
 
		psql -f local.sql mydbuser

4. You can then login Wastebug with user "admin" with password "admin".
	Remember to change your password. You can change other info and create
	more accounts by clicking the "administrator" link on the front page.

# Customization

 Certain parts of Wastebug can be customized directly by modifying the
 database. Notably, the descriptions of priorities, types of cases and
 their icons, and names of status values are stored in the database.

 There are however some things to remember:
 
  - The 'id' fields of priorities and types are used for sorting, so it is a
    good idea to keep those in order.
    
  - The coloring/styling of titles in bug lists uses the status text. The CSS
    class name is constructed by lowercasing the description, then removing all
    characters except letters from 'a' to 'z' (eg. "F1x'D" would become "fxd").
    If you change the texts, you probably want to make the changes to CSS too.

  - The 'open' field of a status determines whether bugs with that status
    are considered open. Closed bugs are only listed in the 'archive'.
