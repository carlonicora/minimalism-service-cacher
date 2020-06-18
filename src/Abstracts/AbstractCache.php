<?php
namespace CarloNicora\Minimalism\Services\Cacher\Abstracts;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\Cacher\Exceptions\CacheKeyNotFoundException;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheFactoryInterface;
use CarloNicora\Minimalism\Services\Cacher\Interfaces\CacheInterface;
use ReflectionClass;
use ReflectionException;

abstract class AbstractCache implements CacheInterface
{
    /** @var array  */
    protected array $stringBuilder = [];

    /** @var array  */
    protected array $parameterValues = [];

    /** @var array  */
    protected array $dependentCaches = [];

    /**
     * @param string $nullReplacement
     * @return string
     * @throws CacheKeyNotFoundException
     */
    private function getKey(string $nullReplacement): string
    {
        $response = 'minimalism-cacher-' . str_replace('\\', '-', static::class);

        $cacheBuilt = false;

        foreach ($this->stringBuilder ?? [] as $key){
            $cacheBuilt = true;
            $response .= '-' . $key . ':';
            if (array_key_exists($key, $this->parameterValues)){
                $response .= $this->parameterValues[$key];
            } else {
                $response .= $nullReplacement;
            }
        }

        if (!$cacheBuilt){
            throw new cacheKeyNotFoundException(self::class);
        }

        return $response;
    }

    /**
     * @return CacheInterface|null
     */
    abstract public function getChildCache(): ?CacheInterface;

    /**
     * @param ServicesFactory $services
     * @param bool $implementGranularCache
     * @return CacheFactoryInterface|null
     */
    abstract public function getChildCacheFactory(ServicesFactory $services, bool $implementGranularCache): ?CacheFactoryInterface;

    /**
     * @return string
     * @throws cacheKeyNotFoundException
     */
    final public function getReadWriteKey(): string
    {
        return $this->getKey('null');
    }

    /**
     * @return array
     * @throws cacheKeyNotFoundException|ReflectionException
     */
    final public function getDeleteKeys(): array
    {
        $response = [];

        $response[] = $this->getKey('*');

        foreach ($this->dependentCaches as $dependentCache){
            if (class_exists($dependentCache)) {
                $dependentCacheClass = new ReflectionClass($dependentCache);
                $constructor = $dependentCacheClass->getConstructor();
                $parameters = $constructor->getParameters();

                $params = [];
                if (count($parameters) > 0) {
                    foreach ($parameters as $parameter) {
                        $parameterName = $parameter->getName();
                        if (array_key_exists($parameterName, $this->parameterValues)) {
                            $params[] = $this->parameterValues[$parameterName];
                            /** @var cacheInterface $dependentCacheInstance */
                            $dependentCacheInstance = $dependentCacheClass->newInstanceArgs($params);

                            /** @noinspection SlowArrayOperationsInLoopInspection */
                            $response = array_merge($response, $dependentCacheInstance->getDeleteKeys());
                        } else {
                            $params[] = null;
                        }
                    }
                } else {
                    /** @var cacheInterface $dependentCacheInstance */
                    $dependentCacheInstance = $dependentCacheClass->newInstance();

                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $response = array_merge($response, $dependentCacheInstance->getDeleteKeys());
                }
            }
        }

        return $response;
    }

    /**
     * @param string $parameterName
     * @param string|null $parameterValue
     */
    final public function addParameterValue(string $parameterName, ?string $parameterValue=null) : void
    {
        if ($parameterValue !== null) {
            $this->parameterValues[$parameterName] = $parameterValue;
        }
    }

    /**
     * @return array
     */
    final public function getStringBuilder() : array
    {
        return $this->stringBuilder;
    }
}