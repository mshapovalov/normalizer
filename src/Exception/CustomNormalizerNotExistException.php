<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class CustomNormalizerNotExistException extends \RuntimeException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Custom normalizer for type "%s" does not exist.', $type));
    }
}
