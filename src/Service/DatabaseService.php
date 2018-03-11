<?php

namespace HotNano\Service;

use Carbon\Carbon;
use HotNano\Entity\Entity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

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

    /**
     * @var bool
     */
    private $modified;

    /**
     * @var bool
     */
    private $loaded;

    public function __construct(string $dbFilePath)
    {
        $this->dbFilePath = $dbFilePath;
        $this->modified = true;
        $this->loaded = false;
    }

    public function loadDb()
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->dbFilePath)) {
            $this->createNewDb();
            $this->saveDb();
        }

        if ($this->loaded) {
            return;
        }

        $this->db = Yaml::parseFile($this->dbFilePath);
        $this->loaded = true;
        $this->modified = false;

        // Convert strings to objects.
        $this->db['created_at'] = Carbon::parse($this->db['created_at']);
        $this->db['updated_at'] = Carbon::parse($this->db['updated_at']);
        $this->db['data']['entities'] = array_map(function (array $a) {
            return Entity::loadFromArray($a);
        }, $this->db['data']['entities']);
    }

    public function saveDb(bool $force = false)
    {
        if (!$this->modified && !$force) {
            return;
        }

        // First clone array.
        $db = $this->db;

        // Then convert objects to strings.
        $db['created_at'] = $this->db['created_at']->format('c');
        $db['updated_at'] = Carbon::now()->format('c');

        $db['data']['entities'] = array_map(function (Entity $entity) {
            return $entity->toArray();
        }, $db['data']['entities']);

        $yml = Yaml::dump($db);

        $fs = new Filesystem();
        $fs->dumpFile($this->dbFilePath, $yml);

        $this->modified = false;
    }

    private function createNewDb()
    {
        $this->db = [
            'version' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'data' => [
                'entities' => [],
            ],
        ];
        $this->loaded = true;
    }

    public function getEntities()
    {
        return $this->db['data']['entities'];
    }

    public function setEntities(array $entities)
    {
        $this->db['data']['entities'] = $entities;
    }
}
