<?php
namespace CarloNicora\Minimalism\Services\Cacher\Commands;

class CacheIdentificatorCommand
{
    /** @var string  */
    private string $name;

    /** @var mixed|null */
    private $identifier;

    /**
     * CacheIdentificationCommand constructor.
     * @param string $name
     * @param $identifier
     */
    public function __construct(string $name, $identifier=null)
    {
        $this->name = $name;
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }
}