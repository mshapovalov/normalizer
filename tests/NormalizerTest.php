<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests;

use DateTime;
use Mshapovalov\Normalizer\Exception\NoMetaDataForDeNormalizationException;
use Mshapovalov\Normalizer\Exception\ThereAreDuplicatedAliasesException;
use Mshapovalov\Normalizer\Exception\ThereAreDuplicatedTypesException;
use Mshapovalov\Normalizer\Exception\ThereIsMoreChangesThanConvertorsException;
use Mshapovalov\Normalizer\Exception\ThereIsNoConfigurationForAliasException;
use Mshapovalov\Normalizer\Exception\ThereIsNoConfigurationForTypeException;
use Mshapovalov\Normalizer\Normalizer;
use Mshapovalov\Normalizer\NormalizerFactory;
use Mshapovalov\Normalizer\NormalizerObserverInterface;
use Mshapovalov\Normalizer\Tests\Mock\InMemoryChangesStorage;
use Mshapovalov\Normalizer\Tests\Stub\Car;
use Mshapovalov\Normalizer\Tests\Stub\Engine;
use Mshapovalov\Normalizer\Tests\Stub\Passenger;
use Mshapovalov\Normalizer\Tests\Stub\TechnicalInspection;
use Mshapovalov\Normalizer\TypeConfiguration;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    private NormalizerFactory $factory;

    private InMemoryChangesStorage $changesStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->changesStorage = new InMemoryChangesStorage();
        $this->factory = new NormalizerFactory($this->changesStorage);
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
        $this->expectExceptionMessage('There are duplicated types "Mshapovalov\Normalizer\Tests\Stub\Car, Mshapovalov\Normalizer\Tests\Stub\Engine"!');
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
                new TypeConfiguration('passenger', Passenger::class),
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
            [$observer],
            true
        );

        $car = new Car('Skoda', new Engine('gasoline', 2.5));
        $technicalInspection = new TechnicalInspection(new \DateTime('2001-01-01 12:00:00'));
        $technicalInspection->addNote('Ok');
        $car->addTechnicalInspection($technicalInspection);
        $car->addPassenger('front', new Passenger('Mike'));
        $car->addPassenger('rear', new Passenger('Joe'));

        $normalized = $normalizer->normalize($car);

        self::assertEquals(
            [
                'model' => 'Skoda',
                'engine' =>
                    [
                        'type' => 'gasoline',
                        'volume' => 2.5,
                        '__normalizer__' =>
                            [
                                'alias' => 'engine',
                                'version' => 0,
                            ],
                    ],
                'technicalInspections' =>
                    [
                        0 =>
                            [
                                'date' =>
                                    [
                                        'value' => '2001:01:01 12:00:00',
                                        '__normalizer__' =>
                                            [
                                                'alias' => 'date',
                                                'version' => 0,
                                            ],
                                    ],
                                'notes' =>
                                    [
                                        0 => 'Ok',
                                    ],
                                '__normalizer__' =>
                                    [
                                        'alias' => 'technicalInspection',
                                        'version' => 0,
                                    ],
                            ],
                    ],
                'passengers' =>
                    [
                        'front' =>
                            [
                                'name' => 'Mike',
                                '__normalizer__' =>
                                    [
                                        'alias' => 'passenger',
                                        'version' => 0,
                                    ],
                            ],
                        'rear' =>
                            [
                                'name' => 'Joe',
                                '__normalizer__' =>
                                    [
                                        'alias' => 'passenger',
                                        'version' => 0,
                                    ],
                            ],
                    ],
                '__normalizer__' =>
                    [
                        'alias' => 'car',
                        'version' => 0,
                    ],
            ],
            $normalized
        );
        $denormalized = $normalizer->denormalize($normalized);
        self::assertEquals($car, $denormalized);
        self::assertTrue($observer->wasObserved());

        $car = new Car('Skoda');
        $normalized = $normalizer->normalize($car);
        self::assertEquals(
            [
                'model' => 'Skoda',
                'engine' => null,
                'technicalInspections' =>
                    [
                    ],
                'passengers' =>
                    [
                    ],
                '__normalizer__' =>
                    [
                        'alias' => 'car',
                        'version' => 0,
                    ],
            ],
            $normalized
        );
        self::assertEquals($car, $normalizer->denormalize($normalized));
    }

    public function testItConvertsData(): void
    {
        $normalizer = $this->factory->createNormalizer(
            [
                new TypeConfiguration(
                    'engine',
                    Engine::class,
                    [
                        function (array $data) {
                            $data['legacy_volume_1'] = $data['legacy_volume_0'];
                            unset($data['legacy_volume_0']);
                            return $data;
                        },
                        function (array $data) {
                            $data['volume'] = $data['legacy_volume_1'];
                            unset($data['legacy_volume_1']);
                            return $data;
                        }
                    ],
                )
            ],
            [],
            true
        );
        $expectedDenormalized = new Engine('diesel', 5);

        $actualDenormalized = $normalizer->denormalize(
            [
                'type' => 'diesel',
                'legacy_volume_0' => 5,
                '__normalizer__' => [
                    'alias' => 'engine',
                    'version' => 0
                ]
            ]
        );
        self::assertEquals($expectedDenormalized, $actualDenormalized);

        $actualDenormalized = $normalizer->denormalize(
            [
                'type' => 'diesel',
                'legacy_volume_1' => 5,
                '__normalizer__' => [
                    'alias' => 'engine',
                    'version' => 1
                ]
            ]
        );
        self::assertEquals($expectedDenormalized, $actualDenormalized);
    }

    public function testItFailsIfThereIsNoMetadata(): void
    {
        $this->expectException(NoMetaDataForDeNormalizationException::class);
        $this->expectExceptionMessage('There is no meta data for denormalization in {"name":"Mike"}!');
        $normalizer = $this->factory->createNormalizer([
            new TypeConfiguration(
                'passenger',
                Passenger::class
            )
        ]);

        $normalizer->denormalize([
            'name' => 'Mike'
        ]);
    }

    public function testIfFailsIfThereIsNoConfigurationForType(): void
    {
        $normalizer = $this->factory->createNormalizer([
            new TypeConfiguration(
                'passenger',
                Passenger::class
            )
        ]);

        $this->expectException(ThereIsNoConfigurationForTypeException::class);
        $this->expectExceptionMessage('There is no normalizer configuration for type "Mshapovalov\Normalizer\Tests\Stub\Engine"!');
        $normalizer->normalize(new Engine('gasoline', 1.2));
    }

    public function testItFailsIfThereNoConfigurationForAlias(): void
    {
        $normalizer = $this->factory->createNormalizer([
            new TypeConfiguration(
                'passenger',
                Passenger::class
            )
        ]);

        $this->expectException(ThereIsNoConfigurationForAliasException::class);
        $this->expectExceptionMessage('There is no normalizer configuration for alias "engine"!');

        $normalizer->denormalize([
            'type' => 'gasoline',
            'value' => 1.2,
            '__normalizer__' => [
                'alias' => 'engine',
                'version' => 0
            ]
        ]);
    }

    public function testItTracksChanges(): void
    {
        $normalizer = $this->factory->createNormalizer(
            [
                new TypeConfiguration(
                    'car',
                    Car::class,
                ),
            ],
            [],
            true
        );
        $normalizer->normalize(new Car('BMW'));
        $normalizer->normalize(new Car('BMW'));
        self::assertEquals(
            [
                'car' =>
                    [
                        0 =>
                            [
                                0 =>
                                    [
                                        'name' => 'model',
                                        'type' => 'string',
                                        'nullable' => false,
                                    ],
                                1 =>
                                    [
                                        'name' => 'engine',
                                        'type' => 'object',
                                        'nullable' => true,
                                    ],
                                2 =>
                                    [
                                        'name' => 'technicalInspections',
                                        'type' => 'array',
                                        'nullable' => false,
                                    ],
                                3 =>
                                    [
                                        'name' => 'passengers',
                                        'type' => 'array',
                                        'nullable' => false,
                                    ],
                            ],
                    ],
            ],
            $this->changesStorage->getChanges()
        );
    }

    public function testItDoesNotNormalizeIfVersionIsWrong()
    {

        $normalizer = $this->factory->createNormalizer(
            [
                new TypeConfiguration(
                    'car',
                    Car::class,
                ),
            ],
            [],
            true
        );
        $normalizer->normalize(new Car('BMW'));

        $normalizer = $this->factory->createNormalizer(
            [
                new TypeConfiguration(
                    'car',
                    Engine::class,
                ),
            ],
            [],
            true
        );
        $this->expectException(ThereIsMoreChangesThanConvertorsException::class);
        $this->expectExceptionMessage('There is more changes than data structure convertors for type "Mshapovalov\Normalizer\Tests\Stub\Engine"!');

        $normalizer->normalize(new Engine('electric', 2000));
    }
}
