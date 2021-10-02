<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests\Stub;


class Car
{
    private string $model;

    private Engine $engine;

    /** @var TechnicalInspection[] */
    private array $technicalInspections = [];

    /**
     */
    public function __construct(string $model, Engine $engine)
    {
        $this->model = $model;
        $this->engine = $engine;
    }

    public function addTechnicalInspection(TechnicalInspection $technicalInspection): void
    {
        $this->technicalInspections[] = $technicalInspection;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getEngine(): Engine
    {
        return $this->engine;
    }

    public function getTechnicalInspections(): array
    {
        return $this->technicalInspections;
    }
}
