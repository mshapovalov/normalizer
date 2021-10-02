<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests\Stub;


class Engine
{
    private string $type;

    private float $volume;

    public function __construct(string $type, float $volume)
    {
        $this->type = $type;
        $this->volume = $volume;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getVolume(): float
    {
        return $this->volume;
    }
}
