<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Services\Cacher\Iterators\CacheIdentificatorsIterator;

class CacheIdentificatorIteratorFactory
{
    /** @var CacheIdentificatorFactory  */
    private CacheIdentificatorFactory $cacheIdentificatorFactory;

    /**
     * CacheIdentificatorIteratorFactory constructor.
     */
    public function __construct()
    {
        $this->cacheIdentificatorFactory = new CacheIdentificatorFactory();
    }

    /**
     * @param string|null $iteratorKeyPart
     * @return CacheIdentificatorsIterator
     */
    public function fromKeyPart(?string $iteratorKeyPart): CacheIdentificatorsIterator
    {
        $response = new CacheIdentificatorsIterator();

        if (!empty($iteratorKeyPart)) {
            foreach (explode('-', $iteratorKeyPart) as $keyPart) {
                $response->addCacheIdentificator(
                    $this->cacheIdentificatorFactory->fromKeyPart($keyPart)
                );
            }
        }

        return $response;
    }
}