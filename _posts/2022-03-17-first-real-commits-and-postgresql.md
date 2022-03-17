---
layout: post
title:  "First real commits and PostgreSQL"
date:   2022-03-17 17:45:06 +0100
categories: github code update
---
First fruits of my labor from the last few weeks are here! In between all my other projects and administrative tasks, I've managed to get to a point that I can commit the first new code to GitHub. This is the result of about a dozen commits to our CVS server, running diffs, merging changes and reviewing the code.

In the meanwhile I've been setting up a PostgreSQL test server. We've got multiple database servers, but they are all MariaDB 10.3 and 5.5. The last time I did something with PostgreSQL was about 15 years ago and I've never installed and configured one. I can say, the design is strange and out of the box it poses a huge security risk if a single server (or cluster as they call it) is used for multiple (web)applications. It's good practice to use different credentials for different databases. This way data stored in for instance Wastebug cannot be exposed through a co-installed Magento install.

PostgreSQL uses roles that per default are mapped to Unix user accounts (peer and ident authentication). This is great at the CLI as an administrator or developer. With MySQL and MariaDB each time you want to do something in the database you must supply a username and password. With PostgreSQL this is only done once, by logging in to the host machine. For traditional applications this is not a problem. You can run your calendar server under a different unix account than your network monitoring server. But with more modern applications, who are often web based, they run all side by side in different virtual hosts on a single Apache, LiteSpeed or NGINX instance. And thus all use the same unix account such as the Apache user and group.

After some reading, testing, failing, retrying and setting up the local firewall, I've got a working PosteSQL server, sorry cluster. Depending on connecting through a Unix domain socket, the loopback interface or to the machines IP address, either peer, ident or md5 authentication is used. Enabling this server to be used for testing most real life scenarios.
