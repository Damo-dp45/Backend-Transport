<?php

namespace App\Entity\Output\Caisse;

final class CaisseParJourDto
{
    public function __construct(
        public readonly string $jour,
        public readonly int    $nbtickets,
        public readonly float  $recette,
        /** @var CaisseDetailVoyageDto[] */
        public readonly array  $detailParVoyage,
    )
    {
    }
}