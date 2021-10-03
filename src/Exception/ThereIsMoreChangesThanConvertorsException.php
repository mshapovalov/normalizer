<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class ThereIsMoreChangesThanConvertorsException extends \RuntimeException
{

    public function __construct(string $type)
    {
        parent::__construct(sprintf('There is more changes than data structure convertors for type "%s"!', $type));
    }
}
