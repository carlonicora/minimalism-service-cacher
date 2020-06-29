<?php
namespace CarloNicora\Minimalism\Services\Cacher;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\Minimalism\Services\Cacher\Configurations\CacheConfigurations;
use CarloNicora\Minimalism\Services\Cacher\Exceptions\CacheNotFoundException;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheInterface;
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
    public Redis $redis;

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
    }

    /**
     * @param CacheInterface $cache
     * @return mixed
     * @throws CacheNotFoundException
     */
    public function read(CacheInterface $cache) : string
    {
        if (!$this->configData->getUseCache()){
            throw new CacheNotFoundException('Cache not enabled');
        }

        $key = $cache->getReadWriteKey();

        try {
            $response = $this->redis->get($key);
        } catch (RedisConnectionException|RedisKeyNotFoundException $e) {
            throw new CacheNotFoundException($key);
        }

        return $response;
    }

    /**
     * @param CacheInterface $cache
     * @return array
     * @throws CacheNotFoundException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function readArray(CacheInterface $cache): array
    {
        if (!$this->configData->getUseCache()){
            throw new CacheNotFoundException('Cache not enabled');
        }

        $response = $this->read($cache);

        try{
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
        }

        return $response;
    }

    /**
     * @param CacheInterface $cache
     * @param $value
     */
    public function create(CacheInterface $cache, string $value) : void
    {
        if (!$this->configData->getUseCache()){
            return;
        }

        $key = $cache->getReadWriteKey();

        try {
            $this->redis->set($key, $value);
        } catch (RedisConnectionException $e) {}
    }

    /**
     * @param CacheInterface $cache
     * @param array $value
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function createArray(CacheInterface $cache, array $value) : void
    {
        if (!$this->configData->getUseCache()){
            return;
        }

        try {
            $stringValue = json_encode($value, JSON_THROW_ON_ERROR, 512);
            $this->create($cache, $stringValue);
        } catch (JsonException $e) {
        }
    }

    /**
     * @param CacheInterface $cache
     */
    public function delete(CacheInterface $cache) : void
    {
        if (!$this->configData->getUseCache()){
            return;
        }

        $keys = $cache->getDeleteKeys();

        $finalKeys = [];

        foreach ($keys ?? [] as $key) {
            if (substr_count($key, '*') > 0){
                try {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $finalKeys = array_merge($finalKeys, $this->redis->getKeys($key));
                } catch (RedisConnectionException $e) {}
            }
        }

        foreach ($finalKeys ?? [] as $key){
            try {
                $this->redis->remove($key);
            } catch (RedisConnectionException $e) {}
        }
    }

    /**
     * @param CacheInterface $cache
     * @param $value
     */
    public function update(CacheInterface $cache, $value): void
    {
        if (!$this->configData->getUseCache()){
            return;
        }

        $this->delete($cache);
        $this->create($cache, $value);
    }

    /**
     * @return bool
     */
    public function useCaching() : bool
    {
        return $this->configData->getUseCache();
    }

    /**
     *
     */
    public function cleanNonPersistentVariables(): void
    {
        unset($this->services);
    }
}