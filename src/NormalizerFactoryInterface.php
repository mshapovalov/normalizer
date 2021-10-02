<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer;


interface NormalizerFactoryInterface
{
    /**
     * @param TypeConfiguration[] $typesConfiguration
     * @param NormalizerObserverInterface[] $observers
     */
    public function createNormalizer(array $typesConfiguration, array $observers): NormalizerInterface;
}
