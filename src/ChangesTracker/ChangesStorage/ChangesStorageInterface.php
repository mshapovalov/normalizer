<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\ChangesTracker\ChangesStorage;

interface ChangesStorageInterface
{
    public function save(string $typeAlias, array $changes): void;

    public function load(string $typeAlias): array;
}
