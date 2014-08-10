<?php
require dirname(__FILE__) . '/../../www/config.php';

if (nZEDb_RELEASE_SEARCH_TYPE != ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in www/settings.php must be set to SPHINX!' . PHP_EOL);
}

if (!isset($argv[1]) || !isset($argv[2]) || !is_numeric($argv[2]) || !isset($argv[3]) || !is_bool((bool)$argv[3])) {
	exit(
		'Argument 1 must the hostname or IP to the Sphinx searchd server, Argument 2 must be the port to the Sphinx searchd server.' . PHP_EOL .
		'The third and final argument is true/false.  True will drop tables if they exist and false will not.' . PHP_EOL);
}

$pdo = new nzedb\db\DB();

$sphinxConnection = sprintf('%s:%d', $argv[1], $argv[2]);

$tables = [];
$tables['releases_se'] =
sprintf(
"CREATE TABLE releases_se
(
	id          BIGINT UNSIGNED NOT NULL,
	weight      INTEGER NOT NULL,
	query       VARCHAR(3072) NOT NULL,
	guid        VARCHAR(40) NOT NULL,
	name        VARCHAR(255) NOT NULL DEFAULT '',
	searchname  VARCHAR(255) NOT NULL DEFAULT '',
	fromname    VARCHAR(255) NULL,
	INDEX(query)
) ENGINE=SPHINX CONNECTION=\"sphinx://%s/releases_rt\"",
$sphinxConnection
);

$tables['releasefiles_se'] =
sprintf(
"CREATE TABLE releasefiles_se
(
	id          BIGINT UNSIGNED NOT NULL,
	weight      INTEGER NOT NULL,
	query       VARCHAR(3072) NOT NULL,
	releaseid   BIGINT UNSIGNED NOT NULL,
	name        VARCHAR(255) NOT NULL DEFAULT '',
	INDEX(query)
) ENGINE=SPHINX CONNECTION=\"sphinx://%s/releasefiles_rt\"",
$sphinxConnection
);

foreach ($tables as $table => $query) {
	if ($argv[3] == true) {
		$pdo->queryExec(sprintf('DROP TABLE IF EXISTS %s', $table));
	}
	$pdo->queryExec($query);
}

echo 'All done! If you messed up your sphinx connection info, you can rerun this script.' . PHP_EOL;