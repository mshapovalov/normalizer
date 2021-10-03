<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\ChangesTracker\ChangesStorage;


class FileChangesStorage implements ChangesStorageInterface
{
    private string $directory;

    /**
     * @var array[]
     */
    private array $cache = [];

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function save(string $typeAlias, array $changes): void
    {
        file_put_contents($this->definePath($typeAlias), json_encode($changes, JSON_PRETTY_PRINT));
        $this->cache[$typeAlias] = $changes;
    }

    public function load(string $typeAlias): array
    {
        if (false === array_key_exists($typeAlias, $this->cache)) {
            $this->cache[$typeAlias] = $this->loadFromFile($typeAlias);
        }
        return $this->cache[$typeAlias];
    }

    private function definePath(string $typeAlias): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $typeAlias . '.json';
    }

    private function loadFromFile(string $typeAlias): array
    {
        $path = $this->definePath($typeAlias);
        if (!file_exists($typeAlias)) {
            return [];
        }
        return json_decode(file_get_contents($path), true);
    }

}
