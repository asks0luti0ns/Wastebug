
<div class="help">
<h2>Wastebug Online Manual:</h2>
<h3>Contents:</h3>
<ul>
<li><a href="#overview">Overview</a></li>
<li><a href="#filing">Filing good bug reports</a></li>
<li><a href="#markup">Markup in comments and news</a></li>
<li><a href="#faq">FAQ</a></li>
</ul>

<h3 id="overview">Overview:</h3>
<p>
Wastebug is a bug-tracking tool.
It is designed to be simple; easy-to-learn and easy-to-use.
The top toolbar provides direct access to most important features.
</p>
<p>
You file new cases by clicking <b>New!</b>. You can browse bugs
assigned to you form <b>My cases</b> or all open cases from <b>List</b>, or
all cases (including closed ones) from <b>Archive</b>. The <b>Projects</b>
page gives some basic statistics about each project.
</p>
<p>
At any time each bug (or case) is assigned to exactly one person.
This person is responsible for either assigning it to someone else,
or solving it. Once solved, cases still stay in the database, so it's
possible to reopen them later by changing status back to one of the
open ones.
</p>
<p>
Any normal user can edit any bug. This is by design, as it allows users to
correct errors, close any bugs they fixed (assigned to them or not), and
generally makes the process more flexible. Subscriptions are personal and if
you subscribe to a bug, you will be sent an email every time someone modifies
the bug.
</p>
<p>
Every change to a bug is logged, with an optional comment.
Simply adding a comment without changing any fields is also possible.
The purpose of log is to provide a complete history of the bug's life,
so that anybody can get an idea of what's been done by simply checking
the bug itself.
</p>

<h3 id="filing">Filing good bug reports:</h3>
<p>
Filing bugs in Wastebug is easy, but it still takes some effort to
produce good bug reports. Here's a basic checklist:
</p>
<?php require_once "inc/config.php"; print $config['checklist']; ?>
<p>
If you provide accurate information for the above questions, it should
be easy for the developers to reproduce the bug. If developers can't
reproduce a bug, they usually can't fix it either. So giving good reports
improves the chances of getting something fixed.
</p>

<h3 id="markup">Markup in comments and news</h3>
<p>
Comments and news postings are pre-formatted into "plain-text" blocks.
You cannot use normal HTML in them. That way you can paste code into the
comments without any special tags, and the comments look the same in the
system and in email notifications sent.
</p>
<p>
That said, there are a few features which the comment formatter supports.
Email addresses and URLs with http, https or ftp as their protocol
automatically become links, so it is good idea to separate them with spaces
from rest of the text. Finally, <code>bug:123</code> turns into a link to bug
number 123.
</p>

<h3 id="faq">FAQ:</h3>
<p><b>Q: How do I post news?</b>
<br />A: You can only do that if you are an administrator.
Administrators have the link on the front-page.
</p>
<p><b>Q: Can I add attachments such as screenshots to bugs?</b>
<br />A: Not yet, you can expect that functionality in future versions.</p>
<p><b>Q: How can I add field 'xyz' to cases?</b>
<br />A: You can't, without editing the code and database.
But please, think twice before adding anything.
Every extra fields makes the interface more cumbersome.
In any case, 'estimate' and 'target version' fields are
currently under consideration. Usually it's better idea to
just add any extra information to the log manually.
</p>
<p><b>Q: How about restricted public access?</b>
<br />A: Restricted public access for filing bugs is on <i>todo</i> list.
</p>
<p><b>Q: Why can't I remove users? What does "disabling" users do?</b>
<br />A: This is a difficult question. The log tracks users based on their
"userid" so that changing user's name changes it everywhere automatically.
Removing a user would mean that either we would have to hardcode user's name in
the log entries he/she has posted, or we would have to assign those entries to
something like "deleted user". We don't want to simply delete them, since they
can contain important information. Using "deleted user" is also sub-optimal,
because it loses an important piece of data, the author of the entry.
</p><p>
That's why you can't currently delete users. Instead, you can disable them.
Disabled users can't login, and no email notifications are sent to them.
Finally, you can't assign a bug to a disabled user, and users can't be disabled
while bugs are assigned to them. A project can't be owned by a disabled user
either, so for all practical purposes a disabled user is like a deleted user,
except you can re-enable it later.
</p>
<p><b>Q: I've found a bug in Wastebug, where do I report it?</b>
<br />A: You can send bug reports to
<a href="mailto:tvoipio@cc.hut.fi">tvoipio@cc.hut.fi</a>.
</div>
