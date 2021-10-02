<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class ThereIsNoConfigurationForTypeException extends \RuntimeException
{

    public function __construct(string $type)
    {
        parent::__construct(sprintf('There is no configuration for type "%s"', $type));
    }
}
