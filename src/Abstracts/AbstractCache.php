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

    /** @var int  */
    protected int $lifespan=0;

    /**
     * AbstractCache constructor.
     * @param array $parameterValues
     */
    public function __construct(array $parameterValues = [])
    {
        $this->parameterValues = $parameterValues;
    }

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
     * @param int $lifespan
     */
    public function setLifespan(int $lifespan): void
    {
        $this->lifespan = $lifespan;
    }

    /**
     * @return int
     */
    public function getLifespan(): int
    {
        return $this->lifespan;
    }

    /**
     * @return CacheInterface|null
     */
    public function getChildCache(): ?CacheInterface
    {
        return null;
    }

    /**
     * @param ServicesFactory $services
     * @param bool $implementGranularCache
     * @return CacheFactoryInterface|null
     */
    public function getChildCacheFactory(ServicesFactory $services, bool $implementGranularCache): ?CacheFactoryInterface
    {
        return null;
    }

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
     */
    public function getDependentCaches(): array
    {
        return [];
    }

    /**
     * @param array $caches
     * @return array
     * @throws CacheKeyNotFoundException
     * @throws ReflectionException
     */
    final public function getDeleteKeys(array &$caches=[]): array
    {
        $response = [];

        if (!in_array(get_class($this), $caches, true)) {

            $response[] = $this->getKey('*');

            $caches[] = get_class($this);

            foreach ($this->getDependentCaches() as $dependentCache) {
                if (class_exists($dependentCache) && !in_array($dependentCache, $caches, true)) {
                    $dependentCacheClass = new ReflectionClass($dependentCache);
                    $constructor = $dependentCacheClass->getConstructor();

                    if ($constructor !== null) {
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
                                    $response = array_merge($response, $dependentCacheInstance->getDeleteKeys($caches));
                                } else {
                                    $params[] = null;
                                }
                            }
                        } else {
                            /** @var cacheInterface $dependentCacheInstance */
                            $dependentCacheInstance = $dependentCacheClass->newInstance();

                            /** @noinspection SlowArrayOperationsInLoopInspection */
                            $response = array_merge($response, $dependentCacheInstance->getDeleteKeys($caches));
                        }
                    }
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

    /**
     * @return array
     */
    public function getParameterValues(): array
    {
        $response = [];
        foreach ($this->stringBuilder as $key){
            $response[] = $this->parameterValues[$key] ?? null;
        }

        return $response;
    }

    /**
     * @return array
    */
    public function getCacheParameters() : array
    {
        return $this->stringBuilder;
    }
}