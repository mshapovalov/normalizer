<?php
declare(strict_types=1);

namespace Mshapovalov\Normalizer\Tests\Stub;

class TechnicalInspection
{
    private \DateTime $date;

    private array $notes = [];

    public function __construct(\DateTime $date)
    {
        $this->date = $date;
    }

    public function addNote(string $note): void
    {
        $this->notes[] = $note;;
    }

    public function getNotes(): array
    {
        return $this->notes;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }
}
