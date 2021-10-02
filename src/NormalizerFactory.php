<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


class NormalizerFactory implements NormalizerFactoryInterface
{

    public function createNormalizer(array $typesConfiguration, array $observers = []): NormalizerInterface
    {
        return new Normalizer($typesConfiguration, $observers);
    }
}
