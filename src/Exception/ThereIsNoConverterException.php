<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class ThereIsNoConverterException extends \RuntimeException
{
    public function __construct(int $dataStructureVersion)
    {
        parent::__construct(sprintf('There is no converter for data structure version %d', $dataStructureVersion));
    }
}
