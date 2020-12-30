<?php
namespace CarloNicora\Minimalism\Services\Cacher;

use CarloNicora\Minimalism\Interfaces\ServiceInterface;
use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;
use CarloNicora\Minimalism\Services\Cacher\Factories\CacheBuilderFactory;
use CarloNicora\Minimalism\Services\Redis\Exceptions\RedisConnectionException;
use CarloNicora\Minimalism\Services\Redis\Exceptions\RedisKeyNotFoundException;
use CarloNicora\Minimalism\Services\Redis\Redis;
use JsonException;

class Cacher implements ServiceInterface
{
    /** @var array  */
    private array $definitions=[];

    /** @var CacheBuilderFactory  */
    private CacheBuilderFactory $factory;

    /**
     * poser constructor.
     * @param Redis $redis
     * @param bool|null $MINIMALISM_SERVICE_CACHER_USE
     */
    public function __construct(private Redis $redis, private ?bool $MINIMALISM_SERVICE_CACHER_USE=null)
    {
        if ($this->MINIMALISM_SERVICE_CACHER_USE === null){
            $this->MINIMALISM_SERVICE_CACHER_USE = false;
        }

        $this->factory = new CacheBuilderFactory();
    }

    /**
     * @return CacheBuilderFactory
     */
    public function getFactory(): CacheBuilderFactory
    {
        return $this->factory;
    }

    /**
     * @param array $definitions
     */
    public function setDefinitions(array $definitions): void
    {
        $this->definitions = $definitions;
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
     * @param CacheBuilder $builder
     * @param string $data
     * @param int $cacheBuilderType
     * @throws RedisConnectionException
     */
    public function save(CacheBuilder $builder, string $data, int $cacheBuilderType): void
    {
        $builder->setType($cacheBuilderType);
        $this->saveCache(
            $builder->getKey(),
            $data,
            $builder->getTtl()
        );
    }

    /**
     * @param CacheBuilder $builder
     * @param array $data
     * @param int $cacheBuilderType
     * @throws JsonException
     * @throws RedisConnectionException
     */
    public function saveArray(CacheBuilder $builder, array $data, int $cacheBuilderType): void
    {
        $jsonData = json_encode($data, JSON_THROW_ON_ERROR);
        $this->save($builder, $jsonData, $cacheBuilderType);

        if (
            array_key_exists(0, $data)
            && $builder->getType() === CacheBuilder::DATA
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
     * @param CacheBuilder $builder
     * @param int $cacheBuilderType
     * @return string|null
     */
    public function read(CacheBuilder $builder, int $cacheBuilderType): ?string
    {
        $builder->setType($cacheBuilderType);
        try {
            return $this->redis->get($builder->getKey());
        } catch (RedisConnectionException|RedisKeyNotFoundException) {
            return null;
        }
    }

    /**
     * @param CacheBuilder $builder
     * @param int $cacheBuilderType
     * @return array|null
     */
    public function readArray(CacheBuilder $builder, int $cacheBuilderType): ?array
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
     * @param CacheBuilder $builder
     * @throws RedisConnectionException
     */
    public function invalidate(CacheBuilder $builder): void
    {
        if ($builder->isList()){
            $this->invalidateList($builder->getKey());
        } elseif ($builder->getShouldInvalidateAllChildren()){
            $this->invalidateChildren($builder->getChildrenKeysPattern());
        } else {
            foreach ($this->definitions[$builder->getCacheName()] ?? [] as $dependentCacheName) {
                $this->invalidateDependents($builder->getChildKeysPattern($dependentCacheName));
            }
        }

        if ($builder->getType() === CacheBuilder::DATA){
             $builder->setType(CacheBuilder::ALL);
             $keys = $this->redis->getKeys($builder->getKeyPattern());
        } else {
            $keys = $builder->getKey();
        }

        $this->redis->remove($keys);
    }

    /**
     * @param string $keysPattern
     * @throws RedisConnectionException
     */
    private function invalidateChildren(string $keysPattern): void
    {
        $childrenKeys = $this->redis->getKeys($keysPattern);

        foreach ($childrenKeys as $childKey){
            $childCacheBuilder = $this->factory->createFromKey($childKey);
            if ($childCacheBuilder->getCacheIdentifier() !== null){
                $childCacheBuilder->clearContexts();
                $childCacheBuilder->setType(CacheBuilder::ALL);

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
            $dependentCacheBuilderInitiator = $this->factory->createFromKey($dependentListCacheKey);
            $dependentListCacheBuilder = $this->factory->createList(
                $dependentCacheBuilderInitiator->getListName(),
                $dependentCacheBuilderInitiator->getCacheName(),
                $dependentCacheBuilderInitiator->getCacheIdentifier()
            )->withType(
                CacheBuilder::ALL
            );
            $this->invalidate($dependentListCacheBuilder);

            if ($dependentCacheBuilderInitiator->getType() !== CacheBuilder::DATA){
                $dependentCacheBuilder = $this->factory->create(
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
        $linkedCacheBuilderInitiator = $this->factory->createFromKey($key);

        $linkedCacheBuilder = $this->factory->createList(
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
     *
     */
    public function initialise(): void {}

    /**
     *
     */
    public function destroy(): void {}
}