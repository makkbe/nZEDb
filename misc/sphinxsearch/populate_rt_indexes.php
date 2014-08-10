<?php
require dirname(__FILE__) . '/../../www/config.php';

if (nZEDb_RELEASE_SEARCH_TYPE != ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in www/settings.php must be set to SPHINX!' . PHP_EOL);
}

if (!isset($argv[1]) || !in_array($argv[1], ['releases_rt', 'releasefiles_rt'])) {
	exit('Argument1 is the index name, releases_rt and releasefiles_rt are the only supported indexes currently.' . PHP_EOL);
}

switch ($argv[1]) {
	case 'releases_rt':
		releases_rt();
		break;
	case 'releasefiles_rt':
		releasefiles_rt();
		break;
	default:
		exit();
}

// Bulk insert releases into sphinx RT index.
function releases_rt()
{
	global $argv;

	$pdo = new nzedb\db\DB();
	$rows = $pdo->queryExec('SELECT id, guid, name, searchname, fromname FROM releases');

	if ($rows !== false && $rows->rowCount()) {
		$sphinx = new SphinxSearch();

		$total = $rows->rowCount();
		$string = 'REPLACE INTO releases_rt (id, guid, name, searchname, fromname) VALUES ';
		$tempString = '';
		$i = 0;
		echo '[Starting to populate sphinx RT index ' . $argv[1] . ' with ' . $total . ' releases.] ';
		foreach ($rows as $row) {
			$i++;
			$tempString .= sprintf(
				'(%d, %s, %s, %s, %s),' ,
				$row['id'],
				$sphinx->sphinxQL->escapeString($row['guid']),
				$sphinx->sphinxQL->escapeString($row['name']),
				$sphinx->sphinxQL->escapeString($row['searchname']),
				$sphinx->sphinxQL->escapeString($row['fromname'])
			);
			if ($i === 1000 || $i >= $total) {
				$sphinx->sphinxQL->queryExec($string . rtrim($tempString, ','));
				$tempString = '';
				$total -= $i;
				$i = 0;
				echo '.';
			}
		}
		echo ' [Done.]' . PHP_EOL;
	} else {
		echo 'No releases in your DB or an error occurred. This will need to be resolved before you can use the search.' . PHP_EOL;
	}
}

// Bulk insert filenames into sphinx RT index.
function releasefiles_rt()
{
	global $argv;

	$pdo = new nzedb\db\DB();
	$rows = $pdo->queryExec('SELECT id, releaseid, name FROM releasefiles');

	if ($rows !== false && $rows->rowCount()) {
		$sphinx = new SphinxSearch();

		$total = $rows->rowCount();
		$string = 'REPLACE INTO releasefiles_rt (id, releaseid, name) VALUES ';
		$tempString = '';
		$i = 0;
		echo '[Starting to populate sphinx RT index ' . $argv[1] . ' with ' . $total . ' filenames.] ';
		foreach ($rows as $row) {
			$i++;
			$tempString .= sprintf(
				'(%d, %d, %s),' ,
				$row['id'],
				$row['releaseid'],
				$sphinx->sphinxQL->escapeString($row['name'])
			);
			if ($i === 1000 || $i >= $total) {
				$sphinx->sphinxQL->queryExec($string . rtrim($tempString, ','));
				$tempString = '';
				$total -= $i;
				$i = 0;
				echo '.';
			}
		}
		echo ' [Done.]' . PHP_EOL;
	} else {
		echo 'No filenames in your DB or an error occurred. (Is PP Additional/PAR2 checking enabled?) This will need to be resolved before you can use the search.' . PHP_EOL;
	}
}