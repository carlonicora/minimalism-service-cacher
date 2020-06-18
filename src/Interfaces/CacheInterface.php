<?php
namespace CarloNicora\Minimalism\Services\Cacher\Interfaces;

interface CacheInterface
{
    /**
     * @return CacheInterface|null
     */
    public function getChildCache(): ?CacheInterface;

    /**
     * @return CacheFactoryInterface|null
     */
    public function getChildCacheFactory(): ?CacheFactoryInterface;

    /**
     * @return string
     */
    public function getReadWriteKey() : string;

    /**
     * @return array
     */
    public function getDeleteKeys() : array;

    /**
     * @param string $parameterName
     * @param string $parameterValue
     */
    public function addParameterValue(string $parameterName, string $parameterValue) : void;
}