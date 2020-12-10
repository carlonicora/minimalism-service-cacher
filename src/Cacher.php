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
        $this->invalidateCache($builder, true);
    }

    /**
     * @param CacheBuilder $builder
     * @param bool $firstRun
     * @throws RedisConnectionException
     */
    private function invalidateCache(CacheBuilder $builder, bool $firstRun): void
    {
        if ($firstRun){
            $cacheBuilderType = CacheBuilder::DATA;
        } else {
            $cacheBuilderType = CacheBuilder::JSON;
        }

        $builder->setType($cacheBuilderType);

        if ($builder->isList()){
            if (($data = $this->readArray($builder, $cacheBuilderType)) !== null && array_key_exists(0, $data))
            {
                foreach($data as $child){
                    if (array_key_exists($builder->getListName(), $child)){
                        $this->invalidateKey(
                            $builder->getListItemKey(
                                $child[$builder->getListName()]
                            )
                        );
                    }
                }
            }
        } elseif (array_key_exists($builder->getCacheName(), $this->definitions) && is_array($this->definitions[$builder->getCacheName()])){
            foreach ($this->definitions[$builder->getCacheName()] ?? [] as $linkedCacheName){
                $childKey = $builder->getChildKey($linkedCacheName);

                $list = $this->redis->getKeys($childKey);

                foreach ($list ?? [] as $child){
                    [,,$type,,$identifier] = explode(':', $child);

                    $childBuilder = $this->factory->create(
                        $linkedCacheName,
                        $identifier
                    );
                    $childBuilder->setType($type === 'DATA' ? CacheBuilder::DATA : CacheBuilder::JSON);
                    $childBuilder->setGroup('*');

                    $parentList = $this->redis->getKeys($childBuilder->getKey());

                    foreach ($parentList ?? [] as $parentKey){
                        $parentBuilder = $this->factory->createFromKey($parentKey);
                        $this->invalidate($parentBuilder);
                    }
                }

                $this->invalidateKey($childKey);
            }
        }

        $this->invalidateKey($builder->getKey());

        if ($firstRun) {
            $this->invalidateCache($builder, false);
        }
    }

    /**
     * @param string $key
     * @throws RedisConnectionException
     */
    private function invalidateKey(string $key): void
    {
        $this->redis->remove($key);
    }
}