<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Trait\PeriodeTrait;
use App\Entity\Output\Financier\CoutParJourDto;
use App\Entity\Output\Financier\FinancierStatistiqueOutput;
use App\Entity\Output\Financier\RecetteParJourDto;
use App\Entity\User;
use App\Repository\ApprovisionnementRepository;
use App\Repository\DepannageRepository;
use App\Repository\TicketRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class FinancierStatsProvider implements ProviderInterface
{
    use PeriodeTrait;

    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private ApprovisionnementRepository $approvisionnementRepository,
        private DepannageRepository $depannageRepository,
        private TicketRepository $ticketRepository
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();
        $identreprise = $user->getEntreprise()->getId();
        $request = $this->requestStack->getCurrentRequest();
        [$dateDebut, $dateFin] = $this->parsePeriode($request);

        // Totaux
        $recettesTotales = $this->ticketRepository->recettesTotales($dateDebut, $dateFin, $identreprise);
        $coutDepannages = $this->depannageRepository->coutTotal($dateDebut, $dateFin, $identreprise);
        $coutApprovisionnements = $this->approvisionnementRepository->coutTotal($dateDebut, $dateFin, $identreprise);
        $beneficeNet = $recettesTotales - $coutDepannages - $coutApprovisionnements;

        // Par jour
        $recettesParJour = array_map(
            fn($row) => new RecetteParJourDto(
                label: $row['label'],
                montant: round((float)$row['montant'], 2)
            ),
            $this->ticketRepository->recettesParJour($dateDebut, $dateFin, $identreprise)
        );

        // Fusion dépannages + appros par jour
        $depannagesParJour = $this->depannageRepository->coutParJour($dateDebut, $dateFin, $identreprise);
        $approsParJour = $this->approvisionnementRepository->coutParJour($dateDebut, $dateFin, $identreprise);

        $coutsMap = [];
        foreach($depannagesParJour as $row) {
            $coutsMap[$row['label']]['depannage'] = (float)$row['montant'];
        }
        foreach($approsParJour as $row) {
            $coutsMap[$row['label']]['approvisionnement'] = (float)$row['montant'];
        }
        ksort($coutsMap);

        $coutsParJour = [];
        foreach($coutsMap as $label => $valeurs) {
            $coutsParJour[] = new CoutParJourDto(
                label: $label,
                depannage: round($valeurs['depannage'] ?? 0, 2),
                approvisionnement: round($valeurs['approvisionnement'] ?? 0, 2),
            );
        }

        return new FinancierStatistiqueOutput(
            recettesTotales:        $recettesTotales,
            coutDepannages:         $coutDepannages,
            coutApprovisionnements: $coutApprovisionnements,
            beneficeNet:            $beneficeNet,
            recettesParJour:        $recettesParJour,
            coutsParJour:           $coutsParJour,
        );
    }
}
