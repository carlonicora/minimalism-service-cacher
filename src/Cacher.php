<?php
namespace CarloNicora\Minimalism\Services\Cacher;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\Minimalism\Services\Cacher\Builders\CacheBuilder;
use CarloNicora\Minimalism\Services\Cacher\Configurations\CacheConfigurations;
use CarloNicora\Minimalism\Services\Cacher\Factories\CacheFactory;
use CarloNicora\Minimalism\Services\Redis\Exceptions\RedisConnectionException;
use CarloNicora\Minimalism\Services\Redis\Exceptions\RedisKeyNotFoundException;
use CarloNicora\Minimalism\Services\Redis\Redis;
use Exception;
use JsonException;

class Cacher extends AbstractService
{
    /** @var CacheConfigurations  */
    public CacheConfigurations $configData;

    /** @var Redis */
    protected Redis $redis;

    /** @var array  */
    private array $definitions=[];

    /** @var CacheFactory  */
    private CacheFactory $factory;

    /**
     * poser constructor.
     * @param ServiceConfigurationsInterface $configData
     * @param ServicesFactory $services
     * @throws ServiceNotFoundException
     * @throws Exception
     */
    public function __construct(ServiceConfigurationsInterface $configData, ServicesFactory $services)
    {
        parent::__construct($configData, $services);

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->configData = $configData;

        $this->redis = $services->service(Redis::class);

        $this->factory = new CacheFactory();
    }

    /**
     * @return CacheFactory
     */
    public function getFactory(): CacheFactory
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
        return $this->configData->getUseCache();
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
        } catch (RedisConnectionException|RedisKeyNotFoundException $e) {
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
        } catch (RedisConnectionException|RedisKeyNotFoundException|JsonException $e) {
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
            [
                ,
                $cacheGroupName,
                $dependentCacheType,
                $dependentCacheGroupName,
                ,
                $dependentCacheName,
                $dependentCacheIdentifier
            ] = explode(':', $builder->getKey());

            $linkedCacheBuilder = $this->factory->createList(
                substr($dependentCacheGroupName, 2),
                substr($dependentCacheName, 2),
                $dependentCacheIdentifier
            );
            $linkedCacheBuilder->setTypeFromString($dependentCacheType);
            $linkedCacheBuilder->setGroup(substr($cacheGroupName, 2));
            $linkedKeys = str_replace('null', '*', $linkedCacheBuilder->getKey());

            if (($linkedCachessKeysList = $this->redis->getKeys($linkedKeys)) !== []){
                $this->redis->remove($linkedCachessKeysList);
            }
        } else {
            foreach ($this->definitions[$builder->getCacheName()] ?? [] as $dependentCacheName) {
                $dependentKey = $builder->getChildKey($dependentCacheName);

                $dependentListCachesKeys = $this->redis->getKeys($dependentKey);

                /*
                 * Deletes all the Cache Lists linked to the cache
                 */
                foreach ($dependentListCachesKeys ?? [] as $dependentListCacheKey) {
                    [
                        ,
                        ,
                        $dependentListCacheType,
                        $dependentListCacheGroupName,
                        ,
                        $dependentListCacheName,
                        $dependentListCacheIdentifier
                    ] = explode(':', $dependentListCacheKey);
                    $dependentListCacheBuilder = $this->factory->createList(
                        substr($dependentListCacheGroupName, 2),
                        substr($dependentListCacheName, 2),
                        $dependentListCacheIdentifier
                    );
                    $dependentListCacheBuilder->setType(CacheBuilder::ALL);
                    $dependentListCacheBuilder->setGroup('*');
                    
                    $this->invalidate($dependentListCacheBuilder);
                    
                    if ($dependentListCacheBuilder->getType() !== CacheBuilder::DATA){
                        $dependentCacheBuilder = $this->factory->create(
                            substr($dependentListCacheName, 2),
                            $dependentListCacheIdentifier
                        );
                        $dependentCacheBuilder->setTypeFromString($dependentListCacheType);
                        $dependentCacheBuilder->setGroup('*');
                        
                        $this->invalidate($dependentCacheBuilder);
                    }
                }
            }
        }

        if ($builder->getType() === CacheBuilder::DATA){
             $builder->setType(CacheBuilder::ALL);
             $keys = $this->redis->getKeys($builder->getKey());
        } else {
            $keys = $builder->getKey();
        }

        $this->redis->remove($keys);
    }
}