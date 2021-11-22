<?php
namespace CarloNicora\Minimalism\Services\Cacher\Iterators;

use CarloNicora\Minimalism\Services\Cacher\Commands\CacheIdentificatorCommand;

class CacheIdentificatorsIterator
{
    /** @var array|CacheIdentificatorCommand[]  */
    private array $cacheIdentificators=[];

    /**
     * @param CacheIdentificatorCommand $cacheIdentificator
     */
    public function addCacheIdentificator(CacheIdentificatorCommand $cacheIdentificator): void
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