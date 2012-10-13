<?php

// Back up Symfony 1 style sites: one folder and one mysql database
// to worry about. See server-backup.php for how we currently handle
// newer projects on which we'd rather just grab /var/www plus all mysql 
// databases plus all mongodb databases

require './config.php';

if (count($argv) < 2)
{
  usage();
}

$dir = $argv[1];
if (!in_array($dir, array("daily", "weekly", "monthly")))
{
  usage();
}

if (count($argv) > 2)
{
  if (count($argv) !== 3)
  {
    usage();
  }
  $host = $argv[2];
}

$count = 0;

foreach ($sites as $site)
{
  if (isset($host))
  {
    if ($site['host'] === $host)
    {
      backup($dir, $keep, $site);
      $count++;
    }
  } 
  else
  {
    backup($dir, $keep, $site);
    $count++;
  }
}

if ($count < 1)
{
  echo("No matching sites to back up! Did you configure sites.php?\n");
  exit(1);
}

function backup($dir, $keep, $site)
{
  $host = $site['host'];
  // sync 1 day ago to 2 days ago, then 0 days ago to 1 days ago, etc.
  // This is efficient because only incremental changes are made.
  //
  // Then we can sync the production content to 0 days ago, which is
  // also incremental
  //
  for ($i = ($keep - 1); ($i >= 0); $i--)
  {
    $older = $i + 1;
    @mkdir("$dir/$host/$i", 0777, true); 
    if ($older < $keep)
    {
      @mkdir("$dir/$host/$older", 0777, true); 
      system("rsync -a --delete $dir/$host/$i/ $dir/$host/$older", $result);
      if ($result != 0) 
      {
        echo("Warning: rotation FAILED: $dir/$host/$older to $dir/$host/$i\n");
      }
    }
  }

  $target = "$dir/$host/0";

  if (!isset($site['port']))
  {
    $site['port'] = 22;
  }
 
  // Carefully worked out to avoid problems with APC, 
  // multiple users, etc. See apostrophe:deploy

  // If you're not sure it's safe to exclude on ALL sites,
  // don't list it in exclude.txt
 
  @mkdir("$target/files", 0777, true);

  mysystem('rsync -qazCcI --exclude-from=exclude.txt --no-t --force --delete --progress -e ' . escapeshellarg('ssh -p ' . $site['port']) . ' ' . escapeshellarg($site['user'] . '@' . $site['host'] . ':' . $site['path'] . '/') . " $target/files/");
  mysystem('ssh -p ' . escapeshellarg($site['port']) . ' ' . escapeshellarg($site['user'] . '@' . $site['host']) . ' ' . escapeshellarg('(cd ' . escapeshellarg($site['path']) . '; ./symfony project:mysql-dump --env=prod )') . " | gzip -c > $target/db.sql.gz");
}

function mysystem($cmd)
{
  system($cmd, $result);
  if ($result != 0)
  {
    echo("WARNING: command FAILED: $cmd\n");
    exit(1);
  }
}

function usage()
{
  echo("Usage: php backup.php daily|weekly [onehostname]\n");
  exit(1);
}

