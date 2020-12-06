<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;

class CacheFactory
{
    /**
     * @param int $type
     * @param string $cacheName
     * @param $identifier
     * @return CacheBuilder
     */
    public function create(
        int $type,
        string $cacheName,
        $identifier
    ): CacheBuilder
    {
        return new CacheBuilder($type, $cacheName, $identifier);
    }

    /**
     * @param int $type
     * @param string $listName
     * @param string $cacheName
     * @param $identifier
     * @param bool $saveGranular
     * @return CacheBuilder
     */
    public function createList(
        int $type,
        string $listName,
        string $cacheName,
        $identifier,
        bool $saveGranular=true
    ): CacheBuilder
    {
        $response = $this->create($type, $cacheName, $identifier);
        $response->setListName($listName);
        $response->setSaveGranular($saveGranular);

        return $response;
    }
}