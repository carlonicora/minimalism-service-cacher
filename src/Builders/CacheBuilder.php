<?php
namespace CarloNicora\Minimalism\Services\Cacher\Builders;

use CarloNicora\Minimalism\Services\Cacher\Commands\CacheIdentificatorCommand;
use CarloNicora\Minimalism\Services\Cacher\Interpreters\CacheKeyInterpreter;
use CarloNicora\Minimalism\Services\Cacher\Iterators\CacheIdentificatorsIterator;

class CacheBuilder
{
    public const ALL=0;
    public const DATA=1;
    public const JSON=2;

    /** @var int  */
    private int $type=self::DATA;

    /** @var CacheIdentificatorCommand|null  */
    private ?CacheIdentificatorCommand $cacheIdentifier=null;

    /** @var CacheIdentificatorCommand|null  */
    private ?CacheIdentificatorCommand $list=null;

    /** @var CacheIdentificatorsIterator  */
    private CacheIdentificatorsIterator $contexts;

    /** @var CacheKeyInterpreter  */
    private CacheKeyInterpreter $interpreter;

    /** @var bool  */
    private bool $saveGranular=true;

    /** @var int|null  */
    private ?int $ttl=null;

    /** @var bool  */
    private bool $invalidateAllChildren=false;

    /** @var bool  */
    private bool $forceContextOnChildren=false;

    /**
     * CacheBuilder constructor.
     */
    public function __construct()
    {
        $this->contexts = new CacheIdentificatorsIterator();
        $this->interpreter = new CacheKeyInterpreter();
    }

    /**
     * @param int $ttl
     * @return CacheBuilder
     */
    public function withTtl(int $ttl): CacheBuilder
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @param string $listName
     * @return CacheBuilder
     */
    public function withList(string $listName): CacheBuilder
    {
        $this->list = new CacheIdentificatorCommand($listName);

        return $this;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function withType(int $type): CacheBuilder
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param bool $saveGranular
     * @return CacheBuilder
     */
    public function withGranularSaveOfChildren(bool $saveGranular): CacheBuilder
    {
        $this->saveGranular = $saveGranular;

        return $this;
    }

    /**
     * @param CacheIdentificatorsIterator $context
     * @return $this
     */
    public function withContexts(CacheIdentificatorsIterator $context): CacheBuilder
    {
        $this->contexts = $context;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $identifier
     * @return CacheBuilder
     */
    public function addContext(string $name, $identifier=null): CacheBuilder
    {
        $this->contexts->addCacheIdentificator(
            new CacheIdentificatorCommand(
                $name,
                $identifier
            )
        );

        return $this;
    }

    /**
     *
     */
    public function clearContexts(): void
    {
        $this->contexts = new CacheIdentificatorsIterator();
    }

    /**
     * @return $this
     */
    public function forcingContextsOnGranularChildren(): CacheBuilder
    {
        $this->forceContextOnChildren = true;

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
     * @param CacheIdentificatorCommand $cacheIdentifier
     */
    public function setCacheIdentifier(CacheIdentificatorCommand $cacheIdentifier): void
    {
        $this->cacheIdentifier = $cacheIdentifier;
    }

    /**
     * @param string $stringType
     */
    public function setTypeFromString(string $stringType): void
    {
        if ($stringType === 'ALL'){
            $this->type = self::ALL;
        } elseif ($stringType === 'DATA'){
            $this->type = self::DATA;
        } else {
            $this->type = self::JSON;
        }
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @param int|null $ttl
     */
    public function setTtl(?int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @param string|null $listName
     */
    public function setListName(?string $listName): void
    {
        if ($this->list === null){
            $this->list = new CacheIdentificatorCommand($listName);
        } else {
            $this->list->setName($listName);
        }
    }

    /**
     * @param CacheIdentificatorsIterator $contexts
     */
    public function setContexts(CacheIdentificatorsIterator $contexts): void
    {
        $this->contexts = $contexts;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCacheName(): string
    {
        return $this->cacheIdentifier->getName();
    }

    /**
     * @return int|string|null
     */
    public function getCacheIdentifier(): int|string|null
    {
        return $this->cacheIdentifier->getIdentifier();
    }

    /**
     * @return bool
     */
    public function getShouldInvalidateAllChildren(): bool
    {
        return $this->invalidateAllChildren;
    }

    /**
     * @return int|null
     */
    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->list !== null;
    }

    /**
     * @return string|null
     */
    public function getListName(): ?string
    {
        return $this->list === null ? null : $this->list->getName();
    }

    /**
     * @return bool
     */
    public function isSaveGranular(): bool
    {
        return $this->saveGranular;
    }

    /**
     * @return CacheIdentificatorsIterator
     */
    public function getContexts(): CacheIdentificatorsIterator
    {
        return $this->contexts;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'minimalism'
            . $this->interpreter->getTypePart($this->type)
            . $this->interpreter->getListKeyPart($this->list)
            . $this->interpreter->getCacheIdentificatorPart($this->cacheIdentifier)
            . $this->interpreter->getCacheContextPart($this->contexts);
    }

    /**
     * @return string
     */
    public function getKeyPattern(): string
    {
        return 'minimalism'
            . $this->interpreter->getAllTypesPart()
            . $this->interpreter->getAllListsKeyPart()
            . $this->interpreter->getCacheIdentificatorPart($this->cacheIdentifier)
            . $this->interpreter->getCacheContextPart($this->contexts);
    }

    /**
     * @param $identifier
     * @return string
     */
    public function getListItemKey($identifier): string
    {
        return 'minimalism'
        . $this->interpreter->getTypePart($this->type)
        . $this->interpreter->getCacheIdentificatorPart($this->cacheIdentifier)
        . $this->interpreter->getCacheIdentificatorPartForAlternateIdentificator($this->list, $identifier)
        . $this->interpreter->getCacheContextPart($this->contexts);
    }

    /**
     * @param $identifier
     * @return string
     */
    public function getGranularKey($identifier): string
    {
        $newCacheIdentifier = new CacheIdentificatorCommand($this->list->getName(), $identifier);

        if ($this->forceContextOnChildren) {
            return 'minimalism'
                . $this->interpreter->getTypePart($this->type)
                . $this->interpreter->getListKeyPart(null)
                . $this->interpreter->getCacheIdentificatorPart($newCacheIdentifier)
                . $this->interpreter->getCacheContextPart($this->contexts);
        }

        return 'minimalism'
            . $this->interpreter->getTypePart($this->type)
            . $this->interpreter->getListKeyPart(null)
            . $this->interpreter->getCacheIdentificatorPart($newCacheIdentifier);
    }

    /**
     * @param string $childCacheName
     * @return string
     */
    public function getChildKeysPattern(string $childCacheName): string
    {
        $newCacheIdentificator = new CacheIdentificatorCommand($childCacheName);

        return 'minimalism'
            . $this->interpreter->getTypePart($this->type)
            . $this->interpreter->getListKeyPart($this->cacheIdentifier)
            . $this->interpreter->getAllCacheIdentificatorParts($newCacheIdentificator)
            . $this->interpreter->getCacheAllContextParts();
    }

    /**
     * @return string
     */
    public function getChildrenKeysPattern(): string
    {
        return 'minimalism'
            . $this->interpreter->getAllTypesPart()
            . $this->interpreter->getAllListsKeyPart()
            . $this->interpreter->getCacheIdentificatorPart($this->cacheIdentifier)
            . $this->interpreter->getCacheContextPart($this->contexts);
    }
}