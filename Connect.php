<?php

class Connect
{
    protected string $host = '127.0.0.1';

    protected string $username = 'root';

    protected string $password = '';

    protected string $database = 'testbench';

    protected string $port = '3306';

    public function __construct(array $credentials)
    {
        // acceptable keys: host, port, database, username, password
        foreach ($credentials as $key => $credential){
            $this->{$key} = $credential;
        }
    }

    public static function credentials(array $credentials): self
    {
        return new self($credentials);
    }

//    public function host(string $host): self
//    {
//        $this->host = $host;
//        return $this;
//    }

//    public function username(string $username): self
//    {
//        $this->username = $username;
//        return $this;
//    }
//
//    public function password(string $password): self
//    {
//        $this->password = $password;
//        return $this;
//    }
//
//    public function database(string $database): self
//    {
//        $this->database = $database;
//        return $this;
//    }
//
//    public function port(string $port): self
//    {
//        $this->port = $port;
//        return $this;
//    }

    protected function connectMysql()
    {
        return new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
    }

    protected function connectSqlsrv()
    {
        $serverName = "$this->host\\sqlexpress, $this->port";

        $connectionInfo = [
            'Database' => $this->database,
            'UID' => $this->username,
            'PWD' => $this->password
        ];

        return sqlsrv_connect($serverName, $connectionInfo);
    }

    protected function execute(string $driver)
    {
        try {
            return match ($driver){
                'sqlsrv' => $this->connectSqlsrv(),
                default => $this->connectMysql()
            };
        } catch (Exception $exception) {
            header('Content-Type: text/html; charset=utf-8');
            die('<span style="font-family: monospace;">Connection failed: ' . $exception->getMessage()) . '</span>';
        }
    }

    public function mssql()
    {
        return $this->execute('sqlsrv');
    }
}