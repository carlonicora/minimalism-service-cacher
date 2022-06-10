<?php
namespace CarloNicora\Minimalism\Services\Cacher\Iterators;

use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheIdentificatorCommandInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheIdentificatorsIteratorInterface;
use CarloNicora\Minimalism\Services\Cacher\Commands\CacheIdentificatorCommand;

class CacheIdentificatorsIterator implements CacheIdentificatorsIteratorInterface
{
    /** @var array|CacheIdentificatorCommand[]  */
    private array $cacheIdentificators=[];

    /**
     * @param CacheIdentificatorCommandInterface $cacheIdentificator
     */
    public function addCacheIdentificator(CacheIdentificatorCommandInterface $cacheIdentificator): void
    {
        $this->cacheIdentificators[] = $cacheIdentificator;
    }

    /**
     * @return array
     */
    public function getCacheIdentificators(): array
    {
        return $this->cacheIdentificators;
    }
}