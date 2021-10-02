<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


use Mshapovalov\Normalizer\Exception\CustomDeNormalizerNotExistException;
use Mshapovalov\Normalizer\Exception\CustomNormalizerNotExistException;

class TypeConfiguration
{
    private string $alias;

    private string $type;

    /**
     * @var callable[]
     */
    private array $converters = [];

    /**
     * @var callable
     */
    private $customNormalizer = null;

    /**
     * @var callable
     */
    private $customDeNormalizer = null;


    public function __construct(
        string $alias,
        string $type,
        array $converters = [],
        callable $customNormalizer = null,
        callable $customDeNormalizers = null
    )
    {
        $this->alias = $alias;
        $this->type = $type;
        $this->converters = $converters;
        $this->customNormalizer = $customNormalizer;
        $this->customDeNormalizer = $customDeNormalizers;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function aliasIsEqualTo(string $alias): bool
    {
        return $this->alias === $alias;
    }

    public function typeIsEqualTo(string $type): bool
    {
        return $this->type === $type;
    }

    public function getDataStructureVersion(): int
    {
        return count($this->converters);
    }

    public function convert(int $dataStructureVersion, array $data): array
    {
        $converter = $this->converters[$dataStructureVersion];
        return $converter($data);
    }

    public function hasCustomNormalizer(): bool
    {
        return null !== $this->customNormalizer;
    }

    public function normalize(object $object): array
    {
        if (false === $this->hasCustomNormalizer()) {
            throw new CustomNormalizerNotExistException($this->type);
        }
        $normalizer = $this->customNormalizer;
        return $normalizer($object);
    }

    public function hasCustomDeNormalizer(): bool
    {
        return null !== $this->customDeNormalizer;
    }

    public function denormalize(array $data): object
    {
        if (false === $this->hasCustomDeNormalizer()) {
            throw new CustomDeNormalizerNotExistException($this->type);
        }
        $deNormalizer = $this->customDeNormalizer;
        return $deNormalizer($data);
    }
}
