<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\ChangesTracker;


use Mshapovalov\Normalizer\ChangesTracker\ChangesStorage\ChangesStorageInterface;

class ObjectStructureChangesTracker
{
    private ChangesStorageInterface $changesStorage;

    public function __construct(ChangesStorageInterface $changesStorage)
    {
        $this->changesStorage = $changesStorage;
    }

    public function trackChanges(string $type, string $typeAlias): void
    {
        $objectStructure = $this->detectObjectStructure($type);
        if ($this->wasStructureChanged($typeAlias, $objectStructure)) {
            $this->changesStorage->save($typeAlias, $objectStructure);
        }
    }

    public function isDataStructureVersionCorrect(string $typeAlias, int $dataStructureVersion): bool
    {
        return count($this->changesStorage->load($typeAlias)) === ($dataStructureVersion + 1);
    }

    private function wasStructureChanged(string $alias, array $objectStructure): bool
    {
        foreach ($this->changesStorage->load($alias) as $change) {
            if ($change === $objectStructure) {
                return false;
            }
        }
        return true;
    }


    private function detectObjectStructure(string $class): array
    {

        $result = [];
        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getProperties() as $property) {
            $propertyData = [
                'name' => $property->getName(),
                'type' => 'mixed'
            ];
            if (null !== $property->getType()) {
                $propertyData = array_merge($propertyData, $this->createTypeFromReflection($property->getType()));
            }
            $result[] = $propertyData;
        }

        return $result;
    }

    private function createTypeFromReflection(\ReflectionType $reflectionType): array
    {
        $type = (string)$reflectionType;
        if (!in_array($type, ['string', 'int', 'float', 'array', 'bool'])) {
            $type = 'object';
        }
        return [
            'type' => $type,
            'nullable' => $reflectionType->allowsNull()
        ];
    }
}
