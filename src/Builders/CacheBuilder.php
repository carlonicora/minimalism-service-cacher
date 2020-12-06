<?php
namespace CarloNicora\Minimalism\Services\Cacher\Builders;

class CacheBuilder
{
    public const DATA=1;
    public const JSON=2;

    /** @var int  */
    private int $type;

    /** @var string  */
    private string $cacheName;

    /** @var mixed */
    private $identifier;

    /** @var string|null  */
    private ?string $listName=null;

    /** @var bool  */
    private bool $saveGranular=true;

    /** @var int|null  */
    private ?int $ttl=null;

    /**
     * CacheBuilder constructor.
     * @param int $type
     * @param string $cacheName
     * @param $identifier
     */
    public function __construct(int $type, string $cacheName, $identifier)
    {
        $this->type = $type;
        $this->cacheName = $cacheName;
        $this->identifier = $identifier;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int|null
     */
    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->listName !== null;
    }

    /**
     * @return string
     */
    public function getCacheName(): string
    {
        return $this->cacheName;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string|null
     */
    public function getListName(): ?string
    {
        return $this->listName;
    }

    /**
     * @param string|null $listName
     */
    public function setListName(?string $listName): void
    {
        $this->listName = $listName;
    }

    /**
     * @param bool $saveGranular
     */
    public function setSaveGranular(bool $saveGranular): void
    {
        $this->saveGranular = $saveGranular;
    }

    /**
     * @return bool
     */
    public function isSaveGranular(): bool
    {
        return $this->saveGranular;
    }

    /**
     * @return string
     */
    private function getBaseKey(): string
    {
        return 'minimalism:'
            . $this->type
            . ':';
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        $response = $this->getBaseKey();

        if ($this->listName !== null){
            $response .= $this->listName
            . ':null:';
        }

        return $response
            . $this->cacheName
            . ':'
            . $this->identifier;
    }

    /**
     * @param $identifier
     * @return string
     */
    public function getListItemKey($identifier): string
    {
        $respose = $this->getKey();
        return str_replace('null', $identifier, $respose);
    }

    /**
     * @param $identifier
     * @return string
     */
    public function getGranularKey($identifier): string
    {
        return $this->getBaseKey()
            . $this->listName
            . ':'
            . $identifier;
    }

    /**
     * @param string $childCache
     * @return string
     */
    public function getChildKey(string $childCache): string
    {
        return $this->getKey()
            . ':'
            . $childCache
            . ':*';
    }
}