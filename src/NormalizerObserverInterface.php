<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


interface NormalizerObserverInterface
{
    public function afterDeNormalize(object $object);
}
