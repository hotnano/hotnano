<?php

namespace HotNano\Service;

/**
 * This handles the YAML 'database' file.
 */
class DatabaseService
{
    /**
     * @var string
     */
    private $dbFilePath;

    /**
     * @var array
     */
    private $db;

    public function __construct(string $dbFilePath)
    {
        $this->dbFilePath = $dbFilePath;
    }

    public function loadDb()
    {

    }

    public function saveDb()
    {

    }
}
