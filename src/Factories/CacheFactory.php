<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;

class CacheFactory
{
    /**
     * @param int $type
     * @param string $group
     * @param string $cacheName
     * @param $identifier
     * @return CacheBuilder
     */
    public function create(
        int $type,
        string $group,
        string $cacheName,
        $identifier
    ): CacheBuilder
    {
        return new CacheBuilder($type, $group, $cacheName, $identifier);
    }

    /**
     * @param int $type
     * @param string $group
     * @param string $listName
     * @param string $cacheName
     * @param $identifier
     * @param bool $saveGranular
     * @return CacheBuilder
     */
    public function createList(
        int $type,
        string $group,
        string $listName,
        string $cacheName,
        $identifier,
        bool $saveGranular=true
    ): CacheBuilder
    {
        $response = $this->create($type, $group, $cacheName, $identifier);
        $response->setListName($listName);
        $response->setSaveGranular($saveGranular);

        return $response;
    }
}