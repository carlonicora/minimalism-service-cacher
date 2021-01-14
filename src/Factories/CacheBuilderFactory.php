<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Interfaces\CacheBuilderFactoryInterface;
use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;

class CacheBuilderFactory implements CacheBuilderFactoryInterface
{
    /** @var CacheIdentificatorFactory  */
    private CacheIdentificatorFactory $cacheIdentificatorFactory;

    /** @var CacheIdentificatorIteratorFactory  */
    private CacheIdentificatorIteratorFactory $cacheIdentificatorIteratorFactory;

    public function __construct()
    {
        $this->cacheIdentificatorFactory = new CacheIdentificatorFactory();
        $this->cacheIdentificatorIteratorFactory = new CacheIdentificatorIteratorFactory();
    }

    /**
     * @param string $cacheName
     * @param $identifier
     * @return CacheBuilder
     */
    public function create(
        string $cacheName,
        $identifier
    ): CacheBuilder
    {
        $response = new CacheBuilder();
        $response->setFullCacheIdentifier(
            $this->cacheIdentificatorFactory->fromNameIdentifier($cacheName, $identifier)
        );

        return $response;
    }

    /**
     * @param string $key
     * @return CacheBuilder
     */
    public function createFromKey(
        string $key
    ): CacheBuilder
    {
        $response = new CacheBuilder();

        [, $type, $list, $cache, $context] = array_pad(explode(':', $key), 5, null);

        $response->setFullCacheIdentifier(
            $this->cacheIdentificatorFactory->fromKeyPart($cache)
        );

        switch ($type) {
            case 'DATA':
                $response->setType(CacheBuilder::DATA);
                break;
            case 'JSON':
                $response->setType(CacheBuilder::JSON);
                break;
            default:
                $response->setType(CacheBuilder::ALL);
        }

        $response->setListName(
            $list
        );

        $response->setContexts(
            $this->cacheIdentificatorIteratorFactory->fromKeyPart($context)
        );

        return $response;
    }

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
    ): CacheBuilder
    {
        $response = $this->create($cacheName, $identifier);
        $response->withList($listName)
            ->withGranularSaveOfChildren($saveGranular);

        return $response;
    }
}