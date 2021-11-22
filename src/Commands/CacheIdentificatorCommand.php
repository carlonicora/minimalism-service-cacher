<?php
namespace CarloNicora\Minimalism\Services\Cacher\Commands;

class CacheIdentificatorCommand
{
    /** @var string  */
    private string $name;

    /** @var mixed|null */
    private mixed $identifier;

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
     * @return int|string|null
     */
    public function getIdentifier(): int|string|null
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
     * @param int|string $identifier
     */
    public function setIdentifier(int|string $identifier): void
    {
        $this->identifier = $identifier;
    }
}