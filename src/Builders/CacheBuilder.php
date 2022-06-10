<?php
namespace CarloNicora\Minimalism\Services\Cacher\Builders;

use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheBuilderInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheIdentificatorCommandInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheIdentificatorsIteratorInterface;
use CarloNicora\Minimalism\Services\Cacher\Commands\CacheIdentificatorCommand;
use CarloNicora\Minimalism\Services\Cacher\Interpreters\CacheKeyInterpreter;
use CarloNicora\Minimalism\Services\Cacher\Iterators\CacheIdentificatorsIterator;

class CacheBuilder implements CacheBuilderInterface
{
    /** @var CacheType  */
    private CacheType $type=CacheType::Data;

    /** @var CacheIdentificatorCommandInterface|null  */
    private ?CacheIdentificatorCommandInterface $cacheIdentifier=null;

    /** @var CacheIdentificatorCommandInterface|null  */
    private ?CacheIdentificatorCommandInterface $list=null;

    /** @var CacheIdentificatorsIteratorInterface  */
    private CacheIdentificatorsIteratorInterface $contexts;

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
     * @return CacheBuilderInterface
     */
    public function withTtl(int $ttl): CacheBuilderInterface
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @param string $listName
     * @return CacheBuilderInterface
     */
    public function withList(string $listName): CacheBuilderInterface
    {
        $this->list = new CacheIdentificatorCommand($listName);

        return $this;
    }

    /**
     * @param CacheType $type
     * @return CacheBuilderInterface
     */
    public function withType(CacheType $type): CacheBuilderInterface
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param bool $saveGranular
     * @return CacheBuilderInterface
     */
    public function withGranularSaveOfChildren(bool $saveGranular): CacheBuilderInterface
    {
        $this->saveGranular = $saveGranular;

        return $this;
    }

    /**
     * @param CacheIdentificatorsIterator $context
     * @return CacheBuilderInterface
     */
    public function withContexts(CacheIdentificatorsIteratorInterface $context): CacheBuilderInterface
    {
        $this->contexts = $context;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $identifier
     * @return CacheBuilderInterface
     */
    public function addContext(string $name, mixed $identifier=null): CacheBuilderInterface
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
     * @return CacheBuilderInterface
     */
    public function forcingContextsOnGranularChildren(): CacheBuilderInterface
    {
        $this->forceContextOnChildren = true;

        return $this;
    }

    /**
     * @return CacheBuilderInterface
     */
    public function invalidateOnlyChildren(): CacheBuilderInterface
    {
        $this->invalidateAllChildren = true;

        return $this;
    }

    /**
     * @param int|string $identifier
     */
    public function setCacheIdentifier(int|string $identifier): void
    {
        $this->cacheIdentifier?->setIdentifier($identifier);
    }

    /**
     * @param CacheIdentificatorCommandInterface $cacheIdentifier
     */
    public function setFullCacheIdentifier(CacheIdentificatorCommandInterface $cacheIdentifier): void
    {
        $this->cacheIdentifier = $cacheIdentifier;
    }

    /**
     * @param string $stringType
     */
    public function setTypeFromString(string $stringType): void
    {
        if ($stringType === CacheType::All->name){
            $this->type = CacheType::All;
        } elseif ($stringType === CacheType::Data->name){
            $this->type = CacheType::Data;
        } else {
            $this->type = CacheType::Json;
        }
    }

    /**
     * @param CacheType $type
     */
    public function setType(CacheType $type): void
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
     * @param CacheIdentificatorsIteratorInterface $contexts
     */
    public function setContexts(CacheIdentificatorsIteratorInterface $contexts): void
    {
        $this->contexts = $contexts;
    }

    /**
     * @return CacheType
     */
    public function getType(): CacheType
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
        return $this->list?->getName();
    }

    /**
     * @return bool
     */
    public function isSaveGranular(): bool
    {
        return $this->saveGranular;
    }

    /**
     * @return CacheIdentificatorsIteratorInterface
     */
    public function getContexts(): CacheIdentificatorsIteratorInterface
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
            . $this->interpreter->getCacheAllContextParts();
    }
}