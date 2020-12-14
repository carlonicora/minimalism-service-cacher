<?php
namespace CarloNicora\Minimalism\Services\Cacher\Interfaces;

use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;

interface CacheFactoryInterface
{
    /**
     * @param string $cacheName
     * @param $identifier
     * @return CacheBuilder
     */
    public function create(
        string $cacheName,
        $identifier
    ): CacheBuilder;

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
    ): CacheBuilder;
}