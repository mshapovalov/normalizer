<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


use Mshapovalov\Normalizer\ChangesTracker\ObjectStructureChangesTracker;
use Mshapovalov\Normalizer\Exception\NoMetaDataForDeNormalizationException;
use Mshapovalov\Normalizer\Exception\ThereAreDuplicatedAliasesException;
use Mshapovalov\Normalizer\Exception\ThereAreDuplicatedTypesException;
use Mshapovalov\Normalizer\Exception\ThereIsMoreChangesThanConvertorsException;
use Mshapovalov\Normalizer\Exception\ThereIsNoConfigurationForAliasException;
use Mshapovalov\Normalizer\Exception\ThereIsNoConfigurationForTypeException;

class Normalizer implements NormalizerInterface
{
    private const ALIAS_PROPERTY_NAME = 'alias';
    private const VERSION_PROPERTY_NAME = 'version';
    private const NORMALIZER_PROPERTY_NAME = '__normalizer__';

    /** @var TypeConfiguration[] */
    private array $typesConfigurations;

    /** @var NormalizerObserverInterface[] */
    private array $observers = [];

    private ObjectStructureChangesTracker $changesTracker;

    private bool $trackChanges;

    public function __construct(
        ObjectStructureChangesTracker $changesTracker,
        array $typesConfigurations,
        bool $trackChanges = false,
        array $observers = []
    )
    {
        $this->assertThereAreNoDuplicates($typesConfigurations);
        $this->typesConfigurations = $typesConfigurations;
        $this->observers = $observers;
        $this->changesTracker = $changesTracker;
        $this->trackChanges = $trackChanges;
    }

    public function normalize(object $object): array
    {
        return $this->normalizeValue($object);
    }

    public function denormalize(array $data): object
    {
        if ($this->isAssociative($data) && false === array_key_exists(self::NORMALIZER_PROPERTY_NAME, $data)) {
            throw new NoMetaDataForDeNormalizationException($data);
        }
        return $this->denormalizeValue($data);
    }

    private function assertThereAreNoDuplicates(array $typesConfigurations): void
    {
        $typesNames = [];
        $aliases = [];
        foreach ($typesConfigurations as $type) {
            $typesNames[] = $type->getType();
            $aliases[] = $type->getAlias();
        }
        $typesNamesDuplicates = $this->getDuplicates($typesNames);
        if ($typesNamesDuplicates) {
            throw new ThereAreDuplicatedTypesException($typesNamesDuplicates);
        }
        $aliasesDuplicates = $this->getDuplicates($aliases);
        if ($aliasesDuplicates) {
            throw new ThereAreDuplicatedAliasesException($aliasesDuplicates);
        }
    }

    public function getDuplicates($array): array
    {
        $unique = array_unique($array);
        return array_diff_assoc($array, $unique);
    }

    /**
     * @return mixed
     */
    private function normalizeValue($value)
    {
        if (is_object($value)) {
            return $this->normalizeObject($value);
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->normalizeValue($v);
            }
            return $value;
        }
        return $value;
    }

    private function normalizeObject(object $object): array
    {
        $configuration = $this->getConfigurationByType(get_class($object));
        if ($this->trackChanges) {
            $this->changesTracker->trackChanges($configuration->getType(), $configuration->getAlias());
            if (false === $this->changesTracker->isDataStructureVersionCorrect($configuration->getAlias(), $configuration->getDataStructureVersion())) {
                throw new ThereIsMoreChangesThanConvertorsException($configuration->getType());
            }
        }
        $result = [];
        if ($configuration->hasCustomNormalizer()) {
            $result = $configuration->normalize($object);
        } else {
            $reflection = new \ReflectionObject($object);
            foreach ($reflection->getProperties() as $property) {
                $result[$property->getName()] = $this->normalizeValue($this->getPropertyValue($property, $object));
            }
        }
        $result[self::NORMALIZER_PROPERTY_NAME] = [
            self::ALIAS_PROPERTY_NAME => $configuration->getAlias(),
            self::VERSION_PROPERTY_NAME => $configuration->getDataStructureVersion()
        ];
        return $result;
    }

    /**
     * @return mixed
     */
    private function denormalizeValue($value)
    {
        if (is_array($value) && array_key_exists(self::NORMALIZER_PROPERTY_NAME, $value)) {
            return $this->denormalizeObject($value);
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->denormalizeValue($v);
            }
            return $value;
        }
        return $value;
    }

    private function denormalizeObject(array $data): object
    {
        $dataStructureVersion = (int)$data[self::NORMALIZER_PROPERTY_NAME][self::VERSION_PROPERTY_NAME];
        $alias = $data[self::NORMALIZER_PROPERTY_NAME][self::ALIAS_PROPERTY_NAME];
        $configuration = $this->getConfigurationByAlias($alias);

        for ($i = $dataStructureVersion; $i < $configuration->getDataStructureVersion(); $i++) {
            $data = $configuration->convert($i, $data);
        }
        if ($configuration->hasCustomDeNormalizer()) {
            $result = $configuration->denormalize($data);
        } else {
            $reflection = new \ReflectionClass($configuration->getType());
            $result = $reflection->newInstanceWithoutConstructor();
            foreach ($reflection->getProperties() as $property) {
                $this->setPropertyValue($property, $result, $this->denormalizeValue($data[$property->getName()] ?? null));
            }
        }

        foreach ($this->observers as $observer) {
            $observer->afterDeNormalize($result);
        }
        return $result;
    }

    private function getConfigurationByType(string $type): TypeConfiguration
    {
        foreach ($this->typesConfigurations as $typeConfiguration) {
            if ($typeConfiguration->typeIsEqualTo($type)) {
                return $typeConfiguration;
            }
        }
        throw new ThereIsNoConfigurationForTypeException($type);
    }

    private function getConfigurationByAlias(string $alias): TypeConfiguration
    {
        foreach ($this->typesConfigurations as $typeConfiguration) {
            if ($typeConfiguration->aliasIsEqualTo($alias)) {
                return $typeConfiguration;
            }
        }
        throw new ThereIsNoConfigurationForAliasException($alias);
    }

    private function getPropertyValue(\ReflectionProperty $property, object $object)
    {
        $property->setAccessible(true);
        $result = $property->getValue($object);
        $property->setAccessible(false);
        return $result;
    }

    private function setPropertyValue(\ReflectionProperty $property, object $object, $value): void
    {
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
    }

    private function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
