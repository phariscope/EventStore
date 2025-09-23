<?php

namespace Phariscope\EventStore\Bridge\Symfony\Factory;

class PdoFactory
{
    private string $dsnTemplate;

    public function __construct(string $dsnTemplate)
    {
        $this->dsnTemplate = $dsnTemplate;
    }

    public function createPdo(): \PDO
    {
        // Évaluer les variables d'environnement à chaque appel
        $dsn = \Phariscope\EventStore\Util\Environment::expand($this->dsnTemplate);

        $pdo = new \PDO($dsn);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
