<?php
namespace CarloNicora\Minimalism\Services\Cacher\Configurations;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceConfigurations;
use CarloNicora\Minimalism\Services\Redis\Redis;

class CacheConfigurations extends AbstractServiceConfigurations
{
    /** @var bool  */
    private bool $useCache;

    /** @var array|string[]  */
    protected array $dependencies = [
        Redis::class
    ];

    /**
     * poserConfigurations constructor.
     */
    public function __construct()
    {
        $this->useCache = filter_var(
            getenv('MINIMALISM_SERVICE_CACHER_USE'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return bool
     */
    public function getUseCache() : bool
    {
        return $this->useCache;
    }
}