<?php

namespace App\Domain\Enum;

enum BagageStatus: string
{
    case STATUT_ENREGISTRE = 'ENREGISTRE';
    case STATUT_EMBARQUE = 'EMBARQUE'; // Lorsque le car démarre auto ou manuel
    case STATUT_LIVRE = 'LIVRE';
    case STATUT_PERDU = 'PERDU';
}