<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;

class CacheFactory
{
    /**
     * @param string $cacheName
     * @param $identifier
     * @return CacheBuilder
     */
    public function create(
        string $cacheName,
        $identifier
    ): CacheBuilder
    {
        return new CacheBuilder($cacheName, $identifier);
    }

    /**
     * @param string $listName
     * @param string $cacheName
     * @param $identifier
     * @param bool $saveGranular
     * @return CacheBuilder
     */
    public function createList(
        string $listName,
        string $cacheName,
        $identifier,
        bool $saveGranular=true
    ): CacheBuilder
    {
        $response = $this->create($cacheName, $identifier);
        $response->setListName($listName);
        $response->setSaveGranular($saveGranular);

        return $response;
    }
}