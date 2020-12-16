<?php
namespace CarloNicora\Minimalism\Services\Cacher\Builders;

class CacheBuilder
{
    public const ALL=0;
    public const DATA=1;
    public const JSON=2;

    /** @var int  */
    private int $type=self::DATA;

    /** @var string  */
    private string $group;

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

    /** @var bool  */
    private bool $invalidateAllChildren=false;

    /**
     * CacheBuilder constructor.
     * @param string $cacheName
     * @param $identifier
     */
    public function __construct(string $cacheName, $identifier)
    {
        $this->group = $cacheName;
        $this->cacheName = $cacheName;
        $this->identifier = $identifier;
    }

    /**
     * @param string $key
     */
    public function rebuildFromKey(string $key): void
    {
        [,$group,$type,$cacheName,$identifier] = explode(':', $key);
        $this->group = substr($group, 2);
        $this->type = (int)substr($type, 2);
        $this->cacheName = substr($cacheName, 2);
        $this->identifier = $identifier;
    }

    /**
     * @param string $stringType
     */
    public function setTypeFromString(string $stringType): void
    {
        if (strpos($stringType, 'T-') !== false){
            $stringType = substr($stringType, 2);
        }

        if ($stringType === '*'){
            $this->type = self::ALL;
        } elseif ($stringType === 'DATA'){
            $this->type = self::DATA;
        } else {
            $this->type = self::JSON;
        }
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return CacheBuilder
     * @param int $type
     */
    public function withType(int $type): CacheBuilder
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return $this
     */
    public function invalidateOnlyChildren(): CacheBuilder
    {
        $this->invalidateAllChildren = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldInvalidateAllChildren(): bool
    {
        return $this->invalidateAllChildren;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    /**
     * @param string $group
     * @return $this
     */
    public function withGroup(string $group): CacheBuilder
    {
        $this->group = $group;

        return $this;
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
        if ($this->type === self::ALL){
            $type = '*';
        } elseif ($this->type === self::DATA){
            $type = 'DATA';
        } else {
            $type = 'JSON';
        }
        
        return 'minimalism:'
            . 'G-' . $this->group
            . ':'
            . 'T-' . $type
            . ':';
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        $response = $this->getBaseKey();

        if ($this->listName !== null){
            $response .= 'L-' . $this->listName
            . ':null:';
        }

        return $response
            . 'N-' . $this->cacheName
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
        $key = str_replace(['N-', 'G-' . $this->group], ['L-', '*'], $this->getKey());
        $key .= ':N-'
            . $childCache
            . ':*';

        return $key;
    }

    /**
     * @param string $childCacheName
     * @return string
     */
    public function getChildrenKeys(string $childCacheName='*'): string
    {
        return 'minimalism:'
            . 'G-*:'
            . 'T-*:'
            . 'L-' . $childCacheName . ':'
            . 'N-' . $this->cacheName . ':'
            . $this->identifier;
    }
}