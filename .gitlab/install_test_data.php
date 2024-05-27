<?php

echo 'SwagMigrationAssistant: install test data script with PHP version ' . phpversion() . PHP_EOL;

function failAssert(bool $condition, string $message) {
    if ($condition)
        return;

    echo "SwagMigrationAssistant: failed asserting with message " . $message . PHP_EOL;
    exit($message);
}

$pluginPath = dirname(__DIR__);
echo "SwagMigrationAssistant: plugin path=" . $pluginPath . PHP_EOL;

$envFilePath = realpath($pluginPath . '/../../../.env');
$env = parse_ini_file($envFilePath);
failAssert($env !== false, 'SwagMigrationAssistant: could not parse env file at path=' . $envFilePath);

// DATABASE_URL="mysql://app:app@127.0.0.1:3306/shopware"
$databaseUrl = $env['DATABASE_URL'];
if ($envDatabaseUrl = getenv('DATABASE_URL')) {
    $databaseUrl = $envDatabaseUrl;
}

$matches = [];
failAssert(preg_match('#//(.+?):(.+?)@(.+?)(?::(.+)/|/)(.+?)$#', $databaseUrl, $matches) === 1, 'SwagMigrationAssistant: could not parse DATABASE_URL');

$user = $matches[1];
$password = $matches[2];
$host = $matches[3];
$port = $matches[4];
$dbName = $matches[5];

echo "SwagMigrationAssistant: found DB host: " . $host . PHP_EOL;
echo "SwagMigrationAssistant: found DB user: " . $user . PHP_EOL;

// this data is there because the testData repository was cloned to there in the Dockerfile
$testDataPath = $pluginPath . '/tests/testData/Migration/sw55.sql';
echo "SwagMigrationAssistant: test data path: " . $testDataPath . PHP_EOL;
failAssert(file_exists($testDataPath), 'test data sql file does not exists');

// import test data
// mysql -u"$user" -p"$password" --host "$host" < tests/testData/Migration/sw55.sql
$cmd = sprintf('mysql -u"%s" -p"%s" --host "%s" < %s', $user, $password, $host, $testDataPath);
$output = [];
$resultCode = 0;
echo "SwagMigrationAssistant: executing mysql command..." . PHP_EOL;
exec($cmd, $output, $resultCode);

foreach ($output as $out) {
    echo $out . PHP_EOL;
}

echo "SwagMigrationAssistant: finished with install test data script" . PHP_EOL;
exit($resultCode);
