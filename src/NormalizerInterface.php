<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


interface NormalizerInterface
{
    public function normalize(object $object):array;

    public function denormalize(array $data):object;
}
