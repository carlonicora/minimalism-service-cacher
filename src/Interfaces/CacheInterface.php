<?php
namespace CarloNicora\Minimalism\Services\Cacher\Interfaces;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;

interface CacheInterface
{
    /**
     * @return CacheInterface|null
     */
    public function getChildCache(): ?CacheInterface;

    /**
     * @param ServicesFactory $services
     * @param bool $implementGranularCache
     * @return CacheFactoryInterface|null
     */
    public function getChildCacheFactory(ServicesFactory $services, bool $implementGranularCache): ?CacheFactoryInterface;

    /**
     * @return string
     */
    public function getReadWriteKey() : string;

    /**
     * @return array
     */
    public function getDeleteKeys() : array;

    /**
     * @param string $parameterName
     * @param string $parameterValue
     */
    public function addParameterValue(string $parameterName, string $parameterValue) : void;
}