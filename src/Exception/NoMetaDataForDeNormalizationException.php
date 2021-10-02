<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Exception;


class NoMetaDataForDeNormalizationException extends \RuntimeException
{
    public function __construct(array $data)
    {
        parent::__construct(sprintf('There is no meta data for denormalization in %s!', json_encode($data)));
    }
}
