<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests\Stub;


class Passenger
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

}
