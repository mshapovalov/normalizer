<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class ThereAreDuplicatedAliasesException extends \InvalidArgumentException
{
    public function __construct(array $duplicates)
    {
        parent::__construct(sprintf('There are duplicated aliases "%s"!', implode(', ', $duplicates)));
    }
}
