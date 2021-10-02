<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests\Stub;


class Car
{
    private string $model;

    private ?Engine $engine;

    /** @var TechnicalInspection[] */
    private array $technicalInspections = [];

    private array $passengers = [];

    /**
     */
    public function __construct(string $model, ?Engine $engine = null)
    {
        $this->model = $model;
        $this->engine = $engine;
    }

    public function addTechnicalInspection(TechnicalInspection $technicalInspection): void
    {
        $this->technicalInspections[] = $technicalInspection;
    }

    public function addPassenger(string $seat, Passenger $passenger): void{
        $this->passengers[$seat] = $passenger;
    }
}
