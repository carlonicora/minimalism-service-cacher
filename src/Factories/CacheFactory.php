<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\Cacher\Events\CacherErrorEvents;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheFactoryInterface;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheInterface;
use ReflectionClass;
use ReflectionException;

class CacheFactory implements CacheFactoryInterface
{
    /** @var ServicesFactory  */
    private ServicesFactory $services;

    /** @var string  */
    public string $cacheClassName;

    /** @var array  */
    public ?array $cacheParameters;

    /** @var bool  */
    public bool $implementsGranularCache;

    /**
     * CacheFactory constructor.
     * @param ServicesFactory $services
     * @param string $cacheClassName
     * @param array $cacheParameters
     * @param bool $implementsGranularCache
     */
    public function __construct(ServicesFactory $services, string $cacheClassName, array $cacheParameters=null, bool $implementsGranularCache = false)
    {
        $this->services = $services;
        $this->cacheClassName = $cacheClassName;
        $this->cacheParameters = $cacheParameters;
        $this->implementsGranularCache = $implementsGranularCache;
    }

    /**
     * @return CacheInterface|null
     */
    public function generateCache() : ?CacheInterface
    {
        try {
            $cacheClass = new ReflectionClass($this->cacheClassName);
            /** @var cacheInterface $response */
            $response = $cacheClass->newInstanceArgs($this->cacheParameters);
        } catch (ReflectionException $e) {
            $this->services->logger()
                ->error()
                ->log(
                    CacherErrorEvents::CACHE_CLASS_NOT_FOUND($this->cacheClassName, $e)
                );
            $response = null;
        }

        return $response;
    }

    /**
     * @param array $granularCacheParameters
     * @return CacheInterface|null
     */
    public function generateGranularCache(array $granularCacheParameters) : ? CacheInterface
    {
        $response = null;

        try {
            $cacheClass = new ReflectionClass($this->cacheClassName);
            /** @var cacheInterface $response */
            $response = $cacheClass->newInstanceArgs($granularCacheParameters);
        } catch (ReflectionException $e) {
            $this->services->logger()
                ->error()
                ->log(
                    CacherErrorEvents::CACHE_CLASS_NOT_FOUND($this->cacheClassName, $e)
                );
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function implementsGranularCache(): bool
    {
        return $this->implementsGranularCache;
    }
}