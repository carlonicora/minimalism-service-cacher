<?php
namespace CarloNicora\Minimalism\Services\Cacher\Events;

use CarloNicora\Minimalism\Core\Events\Abstracts\AbstractErrorEvent;
use CarloNicora\Minimalism\Core\Events\Interfaces\EventInterface;
use CarloNicora\Minimalism\Core\Modules\Interfaces\ResponseInterface;
use Exception;

class CacherErrorEvents extends AbstractErrorEvent
{
    /** @var string  */
    protected string $serviceName='cacher';

    public static function CACHE_CLASS_NOT_FOUND(string $className, Exception $e) : EventInterface
    {
        return new self(
            1,
            ResponseInterface::HTTP_STATUS_500,
            'Cache class not found: %s',
            [$className],
            $e
        );
    }
}