---
layout: post
title:  "The hard work of unforking"
date:   2022-03-09 18:47:06 +0100
categories: github code update
---
There were two reasons to revive Wastebug: 1) being in need of a good[^1] tool, 2) having used a good tool for many years, but having lost some of the code. So far I've augmented all the code and have a working version of Litterbug running on an internal server. I've ported over nearly 300 bugs, feature requests and tasks from a Libre Office Calc spreadsheet. Slowly I'm working out all the kinks, trying to avoid the temptation of working on full integration with our current ERP solution. And also avoiding the temptation of re-implementing previous features of which the code has been lost but are still in my head.

I'm nearing a point that I can start the process of unforking all the changes back into Wastebug 0.9.1 and release 0.9.2, the first public release in 18 years!

[^1]: A good tool as in a tool that:
	- tracks the things to do,
	- supports multiple users,
	- handles many cases for many projects,
	- does not force micromanagement to your workflow,
	- has a simple and quick to use (may have a steep learning curve) interface,
	- is easily modified and extended.
