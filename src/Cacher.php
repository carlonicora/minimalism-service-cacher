<?php
namespace CarloNicora\Minimalism\Services\Cacher;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Interfaces\Cache\Abstracts\AbstractCacheBuilderFactory;
use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheBuilderInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheInterface;
use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;
use CarloNicora\Minimalism\Services\Cacher\Factories\CacheBuilderFactory;
use CarloNicora\Minimalism\Services\Redis\Exceptions\RedisConnectionException;
use CarloNicora\Minimalism\Services\Redis\Exceptions\RedisKeyNotFoundException;
use CarloNicora\Minimalism\Services\Redis\Redis;
use JsonException;

class Cacher extends AbstractService implements CacheInterface
{
    /**
     * poser constructor.
     * @param Redis $redis
     * @param bool|null $MINIMALISM_SERVICE_CACHER_USE
     */
    public function __construct(
        private Redis $redis,
        private ?bool $MINIMALISM_SERVICE_CACHER_USE=null,
    )
    {
        if ($this->MINIMALISM_SERVICE_CACHER_USE === null){
            $this->MINIMALISM_SERVICE_CACHER_USE = false;
        }
    }

    public function initialise(): void
    {
        AbstractCacheBuilderFactory::setCacheInterface($this);
    }

    /**
     * @return string|null
     */
    public static function getBaseInterface(
    ): ?string
    {
        return CacheInterface::class;
    }

    /**
     * @return bool
     */
    public function useCaching() : bool
    {
        return $this->MINIMALISM_SERVICE_CACHER_USE ?? false;
    }

    /**
     * @param string $key
     * @param string $data
     * @param int|null $ttl
     * @throws RedisConnectionException
     */
    private function saveCache(string $key, string $data, ?int $ttl): void
    {
        $this->redis->set(
            $key,
            $data,
            $ttl
        );
    }

    /**
     * @param CacheBuilderInterface $builder
     * @param string $data
     * @param CacheType $cacheBuilderType
     * @throws RedisConnectionException
     */
    public function save(CacheBuilderInterface $builder, string $data, CacheType $cacheBuilderType): void
    {
        $builder->setType($cacheBuilderType);
        $this->saveCache(
            $builder->getKey(),
            $data,
            $builder->getTtl()
        );
    }

    /**
     * @param CacheBuilderInterface $builder
     * @param array $data
     * @param CacheType $cacheBuilderType
     * @throws JsonException
     * @throws RedisConnectionException
     */
    public function saveArray(CacheBuilderInterface $builder, array $data, CacheType $cacheBuilderType): void
    {
        $jsonData = json_encode($data, JSON_THROW_ON_ERROR);
        $this->save($builder, $jsonData, $cacheBuilderType);

        if (
            array_key_exists(0, $data)
            && $builder->getType() === CacheType::Data
            && $builder->isList()
        ) {
            foreach ($data as $child){
                if (array_key_exists($builder->getListName(), $child)){
                    $key = $builder->getListItemKey(
                        $child[$builder->getListName()]
                    );

                    $this->saveCache($key, '*', $builder->getTtl());

                    if ($builder->isSaveGranular()){
                        $key = $builder->getGranularKey(
                            $child[$builder->getListName()]
                        );

                        $this->saveCache(
                            $key,
                            json_encode($child, JSON_THROW_ON_ERROR),
                            $builder->getTtl()
                        );
                    }
                }
            }
        }
    }

    /**
     * @param CacheBuilderInterface $builder
     * @param CacheType $cacheBuilderType
     * @return string|null
     */
    public function read(CacheBuilderInterface $builder, CacheType $cacheBuilderType): ?string
    {
        $builder->setType($cacheBuilderType);
        try {
            return $this->redis->get($builder->getKey());
        } catch (RedisConnectionException|RedisKeyNotFoundException) {
            return null;
        }
    }

    /**
     * @param CacheBuilderInterface $builder
     * @param CacheType $cacheBuilderType
     * @return array|null
     */
    public function readArray(CacheBuilderInterface $builder, CacheType $cacheBuilderType): ?array
    {
        $builder->setType($cacheBuilderType);
        try {
            $jsonData = $this->redis->get($builder->getKey());
            return json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        } catch (RedisConnectionException|RedisKeyNotFoundException|JsonException) {
            return null;
        }
    }

    /**
     * @param CacheBuilderInterface $builder
     * @throws RedisConnectionException
     */
    public function invalidate(CacheBuilderInterface $builder): void
    {
        if ($builder->isList()){
            $this->invalidateList($builder->getKey());
        } elseif ($builder->getShouldInvalidateAllChildren()){
            $this->invalidateChildren($builder->getKeyPattern());
        } else {
            $definitions = $this->getDependents($builder);
            foreach ($definitions ?? [] as $dependentCacheName) {
                $this->invalidateDependents($builder->getChildKeysPattern($dependentCacheName));
            }
        }

        if ($builder->getType() === CacheType::Data){
             $builder->setType(CacheType::All);
             $keys = $this->redis->getKeys($builder->getChildrenKeysPattern());
        } else {
            $keys = $builder->getKey();
        }

        $this->redis->remove($keys);
    }

    /**
     * @param CacheBuilderInterface|CacheBuilder $builder
     * @return array
     * @throws RedisConnectionException
     */
    private function getDependents(CacheBuilderInterface|CacheBuilder $builder): array
    {
        $dependentCacheBuilderKeys = $builder->getChildrenKeysPattern();
        $keys = $this->redis->getKeys($dependentCacheBuilderKeys);

        $response = [];
        foreach ($keys ?? [] as $singleKey) {
            $dependentCache = CacheBuilderFactory::createFromKey($singleKey);
            if (
                $dependentCache->getListName() !== null
                &&
                !in_array($dependentCache->getListName(), $response, true)
            ) {
                $response[] = $dependentCache->getListName();
            }
        }

        return $response;
    }

    /**
     * @param string $keysPattern
     * @throws RedisConnectionException
     */
    private function invalidateChildren(string $keysPattern): void
    {
        $childrenKeys = $this->redis->getKeys($keysPattern);

        foreach ($childrenKeys as $childKey){
            $childCacheBuilder = CacheBuilderFactory::createFromKey($childKey);
            if ($childCacheBuilder->getCacheIdentifier() !== null){
                $childCacheBuilder->clearContexts();
                $childCacheBuilder->setType(CacheType::All);

                $this->invalidate($childCacheBuilder);
            }
        }

        $this->redis->remove($childrenKeys);
    }

    /**
     * @param string $dependentKey
     * @throws RedisConnectionException
     */
    private function invalidateDependents(string $dependentKey): void
    {
        $dependentListCachesKeys = $this->redis->getKeys($dependentKey);

        /*
         * Deletes all the Cache Lists linked to the cache
         */
        foreach ($dependentListCachesKeys ?? [] as $dependentListCacheKey) {
            $dependentCacheBuilderInitiator = CacheBuilderFactory::createFromKey($dependentListCacheKey);
            $dependentListCacheBuilder = CacheBuilderFactory::createList(
                $dependentCacheBuilderInitiator->getListName(),
                $dependentCacheBuilderInitiator->getCacheName(),
                $dependentCacheBuilderInitiator->getCacheIdentifier()
            )->withType(
                CacheType::All
            );
            $this->invalidate($dependentListCacheBuilder);

            if ($dependentCacheBuilderInitiator->getType() !== CacheType::All){
                $dependentCacheBuilder = CacheBuilderFactory::create(
                    $dependentCacheBuilderInitiator->getCacheName(),
                    $dependentCacheBuilderInitiator->getCacheIdentifier()
                )->withType($dependentCacheBuilderInitiator->getType());

                $this->invalidate($dependentCacheBuilder);
            }
        }
    }

    /**
     * @param string $key
     * @throws RedisConnectionException
     */
    private function invalidateList(string $key): void
    {
        $linkedCacheBuilderInitiator = CacheBuilderFactory::createFromKey($key);

        $linkedCacheBuilder = CacheBuilderFactory::createList(
            $linkedCacheBuilderInitiator->getListName(),
            $linkedCacheBuilderInitiator->getCacheName(),
            $linkedCacheBuilderInitiator->getCacheIdentifier()
        )->withType(
            $linkedCacheBuilderInitiator->getType()
        )->withContexts(
            $linkedCacheBuilderInitiator->getContexts()
        );

        if (($linkedCachessKeysList = $this->redis->getKeys($linkedCacheBuilder->getKey())) !== []){
            $this->redis->remove($linkedCachessKeysList);
        }
    }

    /**
     * @param string $baseFactory
     * @return string
     */
    public function getBuilderFactory(
        string $baseFactory,
    ): string
    {
        return CacheBuilderFactory::class;
    }
}