<?php
namespace CarloNicora\Minimalism\Services\Cacher\Interfaces;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;

interface CacheFactoryInterface
{
    /**
     * CacheFactory constructor.
     * @param ServicesFactory $services
     * @param string $cacheClassName
     * @param array|null $cacheParameters
     * @param bool $implementsGranularCache
     */
    public function __construct(ServicesFactory $services, string $cacheClassName, array $cacheParameters=null, bool $implementsGranularCache=false);

    /**
     * @return CacheInterface|null
     */
    public function generateCache() : ?CacheInterface;

    /**
     * @param array $granularCacheParameters
     * @return CacheInterface|null
     */
    public function generateGranularCache(array $granularCacheParameters) : ? CacheInterface;

    /**
     * @return bool
     */
    public function implementsGranularCache() : bool;
}