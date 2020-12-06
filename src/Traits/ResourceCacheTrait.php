<?php
namespace CarloNicora\Minimalism\Services\Cacher\Traits;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\Cacher\Factories\CacheFactory;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheFactoryInterface;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheInterface;

trait ResourceCacheTrait
{
    /** @var ServicesFactory  */
    protected ServicesFactory $services;

    /**
     * @return array
     */
    abstract protected function getParameterValues(): array;

    /**
     * @return string
     */
    private function getParentClass(): string
    {
        return get_parent_class($this);
    }

    /**
     * @return CacheInterface|null
     */
    public function getChildCache(): ?CacheInterface
    {
        $parentClassName = $this->getParentClass();

        /** @var CacheInterface $response */
        return new $parentClassName(...$this->getParameterValues());
    }

    /**
     * @param ServicesFactory $services
     * @param bool $implementGranularCache
     * @return CacheFactoryInterface|null
     */
    public function getChildCacheFactory(ServicesFactory $services, bool $implementGranularCache): ?CacheFactoryInterface
    {
        return new CacheFactory(
            $services,
            $this->getParentClass(),
            $this->getParameterValues(),
            $implementGranularCache
        );
    }
}