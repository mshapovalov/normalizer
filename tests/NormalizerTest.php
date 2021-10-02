<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests;

use DateTime;
use Mshapovalov\Normalizer\Exception\ThereAreDuplicatedAliasesException;
use Mshapovalov\Normalizer\Exception\ThereAreDuplicatedTypesException;
use Mshapovalov\Normalizer\Normalizer;
use Mshapovalov\Normalizer\NormalizerFactory;
use Mshapovalov\Normalizer\NormalizerObserverInterface;
use Mshapovalov\Normalizer\Tests\Stub\Car;
use Mshapovalov\Normalizer\Tests\Stub\Engine;
use Mshapovalov\Normalizer\Tests\Stub\TechnicalInspection;
use Mshapovalov\Normalizer\TypeConfiguration;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    private NormalizerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new NormalizerFactory();
    }

    public function testItCreates(): void
    {
        $normalizer = $this->factory->createNormalizer([
            new TypeConfiguration('car', Car::class)
        ]);
        self::assertInstanceOf(Normalizer::class, $normalizer);
    }

    public function testItDoesNotCreateWithDuplicatedTypes(): void
    {
        $this->expectException(ThereAreDuplicatedTypesException::class);
        $this->expectExceptionMessage('There are duplicated types "Stub\Car, Stub\Engine"!');
        $this->factory->createNormalizer([
            new TypeConfiguration('car', Car::class),
            new TypeConfiguration('car2', Car::class),
            new TypeConfiguration('engine', Engine::class),
            new TypeConfiguration('engine2', Engine::class)
        ]);
    }

    public function testItDoesNotCreateWithDuplicatedAliases(): void
    {
        $this->expectException(ThereAreDuplicatedAliasesException::class);
        $this->expectExceptionMessage('There are duplicated aliases "car, engine"!');
        $this->factory->createNormalizer([
            new TypeConfiguration('car', Car::class),
            new TypeConfiguration('car', Engine::class),
            new TypeConfiguration('engine', TechnicalInspection::class),
            new TypeConfiguration('engine', \DateTime::class)
        ]);
    }

    public function testItNormalizesAndDenormalizes(): void
    {
        $observer = new class implements NormalizerObserverInterface {
            private bool $wasObserved = false;

            public function afterDeNormalize(object $object)
            {
                $this->wasObserved = true;
            }

            public function wasObserved(): bool
            {
                return $this->wasObserved;
            }
        };
        $normalizer = $this->factory->createNormalizer(
            [
                new TypeConfiguration('car', Car::class),
                new TypeConfiguration('engine', Engine::class),
                new TypeConfiguration('technicalInspection', TechnicalInspection::class),
                new TypeConfiguration(
                    'date',
                    \DateTime::class,
                    [
                    ],
                    function (DateTime $date) {
                        return ['value' => $date->format('Y:m:d H:i:s')];
                    },
                    function (array $data) {
                        return new DateTime($data['value']);
                    }
                )
            ],
            [$observer]
        );

        $car = new Car('Skoda', new Engine('gasoline', 2.5));
        $technicalInspection = new TechnicalInspection(new \DateTime('2001-01-01 12:00:00'));
        $technicalInspection->addNote('Ok');
        $car->addTechnicalInspection($technicalInspection);

        $normalized = $normalizer->normalize($car);

        self::assertEquals(
            [
                'model' => 'Skoda',
                'engine' =>
                    [
                        'type' => 'gasoline',
                        'volume' => 2.5,
                        '__type_alias__' => 'engine',
                        '__data_structure_version__' => 0,
                    ],
                'technicalInspections' =>
                    [
                        0 =>
                            [
                                'date' =>
                                    [
                                        'value' => '2001:01:01 12:00:00',
                                        '__type_alias__' => 'date',
                                        '__data_structure_version__' => 0,
                                    ],
                                'notes' =>
                                    [
                                        0 => 'Ok',
                                    ],
                                '__type_alias__' => 'technicalInspection',
                                '__data_structure_version__' => 0,
                            ],
                    ],
                '__type_alias__' => 'car',
                '__data_structure_version__' => 0,
            ],
            $normalized
        );
        $denormalized = $normalizer->denormalize($normalized);
        self::assertEquals($car, $denormalized);
        self::assertTrue($observer->wasObserved());
    }

    public function testItConvertsData():void{
        $normalizer = $this->factory->createNormalizer([
            new TypeConfiguration(
                'engine',
                Engine::class,
                [
                    function (array $data){
                        $data['legacy_volume_1'] = $data['legacy_volume_0'];
                        unset($data['legacy_volume_0']);
                        return $data;
                    },
                    function (array $data){
                        $data['volume'] = $data['legacy_volume_1'];
                        unset($data['legacy_volume_1']);
                        return $data;
                    }
                ],
            )
        ]);
        $expectedDenormalized = new Engine('diesel', 5);

        $actualDenormalized = $normalizer->denormalize(
            [
                'type' => 'diesel',
                'legacy_volume_0' => 5,
                '__type_alias__' => 'engine',
                '__data_structure_version__' => 0,
            ]
        );
        self::assertEquals($expectedDenormalized, $actualDenormalized);

        $actualDenormalized = $normalizer->denormalize(
            [
                'type' => 'diesel',
                'legacy_volume_1' => 5,
                '__type_alias__' => 'engine',
                '__data_structure_version__' => 1,
            ]
        );
        self::assertEquals($expectedDenormalized, $actualDenormalized);
    }
}
