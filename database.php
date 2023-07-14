<?php

namespace database;

use PDO;

function connect(string $host, string $dbname, string $user, string $pass): PDO
{
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
}

class Connection
{
    public $pdo;
    public $stmt;

    function __construct(string $host, string $dbname,
                         string $user, string $pass)
    {
        $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    function exec(string $q, array $vals = []): void
    {
        $this->stmt = $this->pdo->prepare($q);
        $this->stmt->execute($vals);
    }

    function query(string $q, array|string|object $vals = [],
                   string|object $container = null): void
    {
        if (gettype($vals) !== "array")
        {
            $container = $vals;
            $vals = [];
        }

        $this->exec($q, $vals);

        if ($container)
        {
            $mode = PDO::FETCH_INTO;
            if (gettype($container) === "string") $mode = PDO::FETCH_CLASS;
            $this->stmt->setFetchMode($mode, $container);
        }
    }

    function query_one(string $q, array|string|object $vals = [],
                       string|object $container = null): mixed
    {
        $this->query($q, $vals, $container);
        return $this->stmt->fetch();
    }

    function query_many(string $q, array|string|object $vals = [],
                        string|object $container = null): array
    {
        $this->query($q, $vals, $container);
        return $this->stmt->fetchAll();
    }

    function last_id(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    function transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    function commit(): bool
    {
        return $this->pdo->commit();
    }

    function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
