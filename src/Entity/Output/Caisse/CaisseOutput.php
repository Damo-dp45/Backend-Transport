<?php

namespace App\Entity\Output\Caisse;

final class CaisseOutput
{
    public function __construct(
        public readonly int   $totalTickets,
        public readonly float $recetteTotale,
        /** @var CaisseParAgentDto[] */
        public readonly array $parAgent,
        /** @var CaisseParJourDto[] */
        public readonly array $parJour,
    )
    {
    }
}