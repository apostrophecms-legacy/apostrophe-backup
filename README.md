# Apostrophe Backup

Apostrophe Backup is a simple tool for backing up one or more
sites built on [Apostrophe 1.5](http://apostrophenow.org). It is
very handy if you have many client sites running on independent
VPSes and would like an independent backup.

**Apostrophe Backup currently assumes that, in addition to backing up
files, you need to back up one and only one MySQL database.** If you have
additional backup needs you'll need to modify this script.

(You may also be able to use this script with non-Apostrophe
Symfony 1.4 projects as long as they use the sfSyncContentPlugin.)

## Configuration

### Specifying Sites to Back Up

Copy `config.php.example` to `config.php`. Then edit it to specify the
sites to be backed up. Also specify how many backups to keep. Keep
in mind that you can run this script for daily, weekly and/or monthly
backups, and they will each keep that many versions.

`config.php` looks like this:

    <?php
   
    $keep = 3;
 
    $sites = array(
      array(
        'host' => 'myclient.com', 
        'user' => 'myclient', 
        'port' => 22, 
        'path' => '/var/www/myclient/symfony'
      )
      // , array(another one), ...
    );

Setting `$keep` to 3 keeps 3 days of backups (if you're running
daily backups). So in the worst case you have data 48 hours old,
24 hours old, and from just a moment ago when your backup completed.

The hostname you specify must accept ssh connections by the user you
specify, on the port you specify. The port defaults to 22. For practical 
use you must set up the account on the remote server to trust an ssh public 
key so that Apostrophe Backup can connect without a password every night. 
[Here's a good HOWTO on setting up a server to let you ssh in without
a password.](http://www.linuxproblem.org/art_9.html) 	

You can specify as many sites to back up as you wish. Just provide 
additional comma-separated associative arrays with the parameters for each one.

### Specifying Files NOT To Back Up

Copy `exclude.txt.example` to `exclude.txt`. Then review it and add
a line for each folder or file that should not be backed up.

Our standard list is a pretty good fit for Apostrophe 1.5 projects. It
excludes the cache folder and various temporary folders. 

## Running Apostrophe Backup

Decide where you want to back up your files. (Please tell me it's
a RAID array or a Drobo.) Then copy this script to
that folder. Let's say it's `/usr/local/remote-backup/a1.5`.

Next, decide on what schedule you're going to do backups. We run
them daily and weekly. monthly is also supported.

**For each site you add, you must first run the script manually
once** and say yes when ssh asks you if you want to accept the identity 
of the site. Here I assume you're going to do daily backups (at least):

    cd /usr/local/remote-backup/a1.5
    php backup.php daily myclient.com

When ssh asks you to accept the identity of the site, say yes. In future
ssh will remember this site.

Once you have done this for all of your sites, you can run a daily backup
of all of them:

    cd /usr/local/remote-backup/a1.5
    php backup.php daily

Or a weekly backup:

    cd /usr/local/remote-backup/a1.5
    php backup.php weekly

## Scheduling Automated Backups

You can schedule a cron job to run backups every night, or every Sunday
for weekly backups. Use `crontab -e` to open your cron settings in a 
text editor. Here's what our settings look like:

    # Every day at 2am, a daily backup
    0 2 * * * (cd /usr/local/remote-backup/a1.5; /usr/bin/php backup.php daily)
    # Every Sunday at 3am, a weekly backup
    0 3 * * 0 (cd /usr/local/remote-backup/a1.5; /usr/bin/php backup.php weekly)

## Restoring Your Backups

Great, you have backups. How do you restore your site in the event your VPS
turns into a jar of Folger's Crystals, or your client asks you to undo
a nasty mistake in their database?

In `/usr/local/remote-backup/a1.5/daily/myclient.com/0/files`, you'll find
the latest backup of myclient.com's Symfony project folder. Restore that
via rsync. Note the trailing slash after `files` to avoid creating an
additional directory level on the destination:

    rsync -a /usr/local/remote-backup/a1.5/daily/myclient.com/0/files/ someuser@someserver:/var/www/myclient.com/symfony

Now push the database back up to the server as well:
   
    cat /usr/local/remote-backup/a1.5/daily/myclient.com/0/db.sql.gz | ssh someuser@someserver gunzip -c | /var/www/myclient.com/symfony/symfony project:mysql-load --env=prod

These are just examples of course. The key thing is that you have a copy of
the symfony project folder and a gzipped mysqldump file which you can
restore as you see fit.

If you need access to older backups, just look at myclient.com/1 
(yesterday), myclient.com/2 (two days ago), etc. 

## Technical Notes

This tool depends on the `project:mysql-dump` task, which is part of
all Apostrophe 1.5 projects that begin life with our sandbox project.
It's part of sfSyncContentPlugin.

Many of our newer projects are built on different technology,
such as Symfony 2, node.js and MongoDB. We have another backup script
that is better suited to such environments and we plan to release
that as well.

## Contributing

Feel free! Please don't break backwards compatibility. Test your code
thoroughly and keep it simple - backups should be boring and reliable, 
not sexy and unfathomable. Be sure to explain what you're trying
to accomplish in your pull requests.

## Contact

Use the [github issue tracker](http://github.com/punkave/apostrophe-backup). 
Also visit [punkave.com](http://punkave.com).

