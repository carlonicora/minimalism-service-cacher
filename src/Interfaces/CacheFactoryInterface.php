<?php
namespace CarloNicora\Minimalism\Services\Cacher\Interfaces;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;

interface CacheFactoryInterface
{
    /**
     * CacheFactory constructor.
     * @param ServicesFactory $services
     * @param string $cacheClassName
     * @param array $cacheParameters
     * @param array|null $granularCacheParameters
     */
    public function __construct(ServicesFactory $services, string $cacheClassName, array $cacheParameters, ?array $granularCacheParameters=null);

    /**
     * @return CacheInterface|null
     */
    public function generateCache() : ?CacheInterface;

    /**
     * @return CacheInterface|null
     */
    public function generateGranularCache() : ? CacheInterface;
}