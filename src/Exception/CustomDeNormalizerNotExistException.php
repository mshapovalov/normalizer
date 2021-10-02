<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class CustomDeNormalizerNotExistException extends \RuntimeException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Custom deNormalizer for type %s does not exist!', $type));
    }
}
