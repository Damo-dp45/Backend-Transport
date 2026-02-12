<?php

namespace App\Entity\Output\Caisse;

final class CaisseParAgentDto
{
    public function __construct(
        public readonly int    $agentId,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly int    $nbtickets,
        public readonly float  $recette,
        /** @var CaisseDetailVoyageDto[] */
        public readonly array  $detailParVoyage,
    )
    {
    }
}