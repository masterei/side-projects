<?php

spl_autoload_register('autoloader');

function autoloader(string $className): void
{
    include_once __DIR__ . "/$className.php";
}

include_once __DIR__ . '/helpers.php';

// sql connection
$server = [
    'driver' => 'sqlsrv',
    'connection' => Connect::credentials([
        'host' => '172.16.0.220',
        'port' => '1433',
        'database' => 'HRIS_DB',
        'username' => 'sa',
        'password' => 'bpms2021!#p@$$w0rd',
    ])->mssql(),

];

global $server;