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
    public array $cacheParameters;

    /** @var array|null  */
    public ?array $granularCacheParameters;

    /**
     * CacheFactory constructor.
     * @param ServicesFactory $services
     * @param string $cacheClassName
     * @param array $cacheParameters
     * @param array|null $granularCacheParameters
     */
    public function __construct(ServicesFactory $services, string $cacheClassName, array $cacheParameters, ?array $granularCacheParameters=null)
    {
        $this->services = $services;
        $this->cacheClassName = $cacheClassName;
        $this->cacheParameters = $cacheParameters;
        $this->granularCacheParameters = $granularCacheParameters;
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
     * @return CacheInterface|null
     */
    public function generateGranularCache() : ? CacheInterface
    {
        $response = null;

        if ($this->granularCacheParameters !== null) {
            try {
                $cacheClass = new ReflectionClass($this->cacheClassName);
                /** @var cacheInterface $response */
                $response = $cacheClass->newInstanceArgs($this->granularCacheParameters);
            } catch (ReflectionException $e) {
                $this->services->logger()
                    ->error()
                    ->log(
                        CacherErrorEvents::CACHE_CLASS_NOT_FOUND($this->cacheClassName, $e)
                    );
            }
        }

        return $response;
    }
}