<?php
namespace CarloNicora\Minimalism\Services\Cacher\Interpreters;

use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Services\Cacher\Commands\CacheIdentificatorCommand;
use CarloNicora\Minimalism\Services\Cacher\Iterators\CacheIdentificatorsIterator;

class CacheKeyInterpreter
{
    /**
     * @return string
     */
    public function getAllTypesPart(): string
    {
        return ':*';
    }

    /**
     * @param CacheType $type
     * @return string
     */
    public function getTypePart(CacheType $type): string
    {
        $response = ':';

        $response .= match ($type) {
            CacheType::Data => CacheType::Data->name,
            CacheType::Json => CacheType::Json->name,
            default => '*',
        };

        return $response;
    }

    /**
     * @return string
     */
    public function getAllListsKeyPart(): string
    {
        return ':*';
    }

    /**
     * @param CacheIdentificatorCommand|null $list
     * @return string
     */
    public function getListKeyPart(?CacheIdentificatorCommand $list): string
    {
        if ($list === null){
            return ':null';
        }

        return $this->getCacheIdentificatorPart($list);
    }

    /**
     * @param CacheIdentificatorCommand $cacheIdentificator
     * @return string
     */
    public function getCacheIdentificatorPart(CacheIdentificatorCommand $cacheIdentificator): string
    {
        if ($cacheIdentificator->getIdentifier() === null){
            return ':'
                . $cacheIdentificator->getName();
        }

        return ':'
            . $cacheIdentificator->getName()
            . '('
            . $cacheIdentificator->getIdentifier()
            .')';
    }

    /**
     * @param CacheIdentificatorCommand $cacheIdentificator
     * @param int|string $alternateIdentificator
     * @return string
     */
    public function getCacheIdentificatorPartForAlternateIdentificator(CacheIdentificatorCommand $cacheIdentificator, int|string $alternateIdentificator): string
    {
        return ':'
            . $cacheIdentificator->getName()
            . '('
            . $alternateIdentificator
            .')';
    }

    /**
     * @param CacheIdentificatorCommand $cacheIdentificator
     * @return string
     */
    public function getAllCacheIdentificatorParts(CacheIdentificatorCommand $cacheIdentificator): string
    {
        return ':'
            . $cacheIdentificator->getName()
            . '(*)';
    }

    /**
     * @param CacheIdentificatorsIterator $cacheIdentificatorsIterator
     * @return string
     */
    public function getCacheContextPart(CacheIdentificatorsIterator $cacheIdentificatorsIterator): string
    {
        if ($cacheIdentificatorsIterator->getCacheIdentificators() === []) {
            return '';
        }

        $cacheIdentificators = $cacheIdentificatorsIterator->getCacheIdentificators();

        $cacheIdentificatorsArray = [];
        foreach ($cacheIdentificators as $cacheIdentificatorCommand){
            $cacheIdentificatorsArray[$cacheIdentificatorCommand->getName()] = $cacheIdentificatorCommand->getIdentifier() ?? 0;
        }

        ksort($cacheIdentificatorsArray);

        $response = ':';

        foreach ($cacheIdentificatorsArray as $cacheName=>$cacheValue){
            $response .= $cacheName
                . '('
                . $cacheValue
                . ')-';
        }

        return substr($response, 0, -1);
    }

    /**
     * @return string
     */
    public function getCacheAllContextParts(): string
    {
        return '*';
    }
}