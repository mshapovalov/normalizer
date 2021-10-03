<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests\Mock;


use Mshapovalov\Normalizer\ChangesTracker\ChangesStorage\ChangesStorageInterface;

class InMemoryChangesStorage implements ChangesStorageInterface
{
    private array $changes = [];

    public function save(string $typeAlias, array $changes): void
    {
        $this->changes[$typeAlias][] = $changes;
    }

    public function load(string $typeAlias): array
    {
        if (array_key_exists($typeAlias, $this->changes)) {
            return $this->changes[$typeAlias];
        }
        return [];
    }

    public function getChanges(): array
    {
        return $this->changes;
    }
}
