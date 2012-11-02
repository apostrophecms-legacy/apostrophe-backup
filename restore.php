<?php

require dirname(__FILE__) . '/restore-config.php';

// Restore a standard Symfony 1 site from backup

if (!file_exists('config/properties.ini'))
{
  echo("I don't see a config/properties.ini file, I don't think you\n");
  echo("are in a Symfony project folder.\n");
  usage();
}

require './lib/vendor/symfony/lib/yaml/sfYaml.php';

if (count($argv) === 1)
{
  usage();
}

$hostname = $argv[1];

if (count($argv) >= 3)
{
  $frequency = $argv[2];
}
else
{
  $frequency = 'daily';
}

if (count($argv) >= 4)
{
  $age = $argv[3];
}
else
{
  $age = 0;
}

$folder = "$rsync/$frequency/$hostname/$age";
go("rsync -av $folder/files/web/uploads/ web/uploads");
go("rsync -av $folder/files/data/a_writable/ data/a_writable");
go("rsync -av $folder/db.sql.gz /tmp/db.sql.gz");

echo("*** An error here is OK if your database already exists\n");
go("./symfony doctrine:build-db");
echo("*** Any error here is NOT OK\n");
go("gunzip -c /tmp/db.sql.gz | ./symfony project:mysql-load");

echo("\nThe site's content should be live in your dev environment.\n\n");
echo("Test the site and use project:sync-content to sync content\n");
echo("back up to the new production environment.\n\n");
echo("If you need to prepare a new production server, use\n");
echo("pksgsite as you normally would. Remove the old production\n");
echo("entries from config/databases.yml and config/properties.ini\n");
echo("before you do so.\n");

function usage()
{
  global $rsync;
  echo("Usage: pkrestore hostname [daily 0]\n\n");
  echo("'hostname' must be the site's backup folder name in:\n\n");
  echo("/usr/local/remote-backup/rotating-symfony1-style/daily\n\n");
  echo("If you do not specify more arguments the latest backup\n\n");
  echo("is restored. Use daily 1 for one day ago, daily 2 for two\n");
  echo("days ago, weekly 1 for one week ago.\n\n");
  echo("Run this script from a Symfony project folder. The site's\n");
  echo("content will be synced from the intranet server's latest\n");
  echo("backup to your local dev environment. Then test the site\n");
  echo("and use project:sync-content to sync content back up to\n");
  echo("the new production environment.\n\n");
  echo("If you need to prepare a new production server, use\n");
  echo("pksgsite as you normally would. Remove the old production\n");
  echo("entries from config/databases.yml and config/properties.ini\n");
  echo("before you do so.\n\n");
  echo("Here is a list of site hostnames that were backed up\n");
  echo("last night:\n\n");
  sshLs("$rsync/daily");
  exit(1);
}

function go($cmd)
{
  global $rsync;
  echo("Executing: $cmd\n");
  system($cmd, $result);
  if ($result != 0)
  {
    echo("Command FAILED: $cmd\n");
    echo("Possibly you have the hostname wrong?\n\n");
    echo("Here's a list:\n\n");
    sshLs("$rsync/daily");
    echo("\n");
    echo("It is also possible that the site doesn't have a backup\n");
    echo("as old as you specified.\n");
    exit(1);
  }
}

function sshLs($path)
{
  if (preg_match('/^(.*)?\:(.*)$/', $path, $matches))
  {
    list($dummy, $creds, $path) = $matches;
    system("ssh $creds ls $path");
  }
  else
  {
    system("ls $path");
  }
}
