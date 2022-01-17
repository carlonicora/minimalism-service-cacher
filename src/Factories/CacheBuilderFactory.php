<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheBuilderFactoryInterface;
use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;

class CacheBuilderFactory implements CacheBuilderFactoryInterface
{
    /** @var CacheIdentificatorFactory|null  */
    private static ?CacheIdentificatorFactory $cacheIdentificatorFactory=null;

    /** @var CacheIdentificatorIteratorFactory|null  */
    private static ?CacheIdentificatorIteratorFactory $cacheIdentificatorIteratorFactory=null;

    /**
     * @return CacheIdentificatorFactory
     */
    private static function getCacheIdentificatorFactory(
    ): CacheIdentificatorFactory
    {
        if (self::$cacheIdentificatorFactory === null) {
            self::$cacheIdentificatorFactory = new CacheIdentificatorFactory();
        }

        return self::$cacheIdentificatorFactory;
    }

    /**
     * @return CacheIdentificatorIteratorFactory
     */
    private static function getCacheIdentificatorIteratorFactory(
    ): CacheIdentificatorIteratorFactory
    {
        if (self::$cacheIdentificatorIteratorFactory === null) {
            self::$cacheIdentificatorIteratorFactory = new CacheIdentificatorIteratorFactory();
        }

        return self::$cacheIdentificatorIteratorFactory;
    }

    /**
     * @param string $cacheName
     * @param $identifier
     * @return CacheBuilder
     */
    public static function create(
        string $cacheName,
        $identifier
    ): CacheBuilder
    {
        $response = new CacheBuilder();
        $response->setFullCacheIdentifier(
            self::getCacheIdentificatorFactory()->fromNameIdentifier($cacheName, $identifier)
        );

        return $response;
    }

    /**
     * @param string $key
     * @return CacheBuilder
     */
    public static function createFromKey(
        string $key
    ): CacheBuilder
    {
        $response = new CacheBuilder();

        [, $type, $list, $cache, $context] = array_pad(explode(':', $key), 5, null);

        $response->setFullCacheIdentifier(
            self::getCacheIdentificatorFactory()->fromKeyPart($cache)
        );

        switch ($type) {
            case CacheType::Data->name:
                $response->setType( CacheType::Data);
                break;
            case CacheType::Json->name:
                $response->setType(CacheType::Json);
                break;
            default:
                $response->setType(CacheType::All);
        }

        if ($list !== 'null') {
            $response->setListName(
                $list
            );
        }

        $response->setContexts(
            self::getCacheIdentificatorIteratorFactory()->fromKeyPart($context)
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
    public static function createList(
        string $listName,
        string $cacheName,
        $identifier,
        bool $saveGranular=true
    ): CacheBuilder
    {
        $response = self::create($cacheName, $identifier);
        $response->withList($listName)
            ->withGranularSaveOfChildren($saveGranular);

        return $response;
    }
}