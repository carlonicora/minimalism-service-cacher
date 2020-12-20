<?php

namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Services\Cacher\Commands\CacheIdentificatorCommand;

class CacheIdentificatorFactory
{
    /**
     * @param string $name
     * @param mixed $identifier
     * @return CacheIdentificatorCommand
     */
    public function fromNameIdentifier(string $name, $identifier): CacheIdentificatorCommand
    {
        return new CacheIdentificatorCommand($name, $identifier);
    }

    /**
     * @param string $keyPart
     * @return CacheIdentificatorCommand
     */
    public function fromKeyPart(string $keyPart): CacheIdentificatorCommand
    {
        preg_match('#\((.*?)\)#', $keyPart, $matches);

        return new CacheIdentificatorCommand($matches[0], $matches[1]);
    }
}