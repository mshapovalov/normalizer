<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


use Mshapovalov\Normalizer\ChangesTracker\ChangesStorage\ChangesStorageInterface;
use Mshapovalov\Normalizer\ChangesTracker\ObjectStructureChangesTracker;

class NormalizerFactory implements NormalizerFactoryInterface
{

    private ChangesStorageInterface $changesStorage;

    public function __construct(ChangesStorageInterface $changesStorage)
    {
        $this->changesStorage = $changesStorage;
    }

    public function createNormalizer(array $typesConfiguration, array $observers = [], bool $trackChanges = false): NormalizerInterface
    {
        return new Normalizer(
            new ObjectStructureChangesTracker($this->changesStorage),
            $typesConfiguration,
            $trackChanges,
            $observers
        );
    }
}
