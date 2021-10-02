<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class ThereIsNoConfigurationForAliasException extends \RuntimeException
{
    public function __construct(string $alias)
    {
        parent::__construct(sprintf('There is no configuration for alias "%s"', $alias));
    }
}
