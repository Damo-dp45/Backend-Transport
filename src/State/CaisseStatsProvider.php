<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Trait\PeriodeTrait;
use App\Entity\Output\Caisse\CaisseDetailVoyageDto;
use App\Entity\Output\Caisse\CaisseOutput;
use App\Entity\Output\Caisse\CaisseParAgentDto;
use App\Entity\Output\Caisse\CaisseParJourDto;
use App\Entity\User;
use App\Repository\TicketRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CaisseStatsProvider implements ProviderInterface
{
    use PeriodeTrait;

    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
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

        $totalTickets  = $this->ticketRepository->countTotal($dateDebut, $dateFin, $identreprise);
        $recetteTotale = $this->ticketRepository->recettesTotales($dateDebut, $dateFin, $identreprise);
        // ── Par agent ────────────────────────────────────────────
        $rawAgents = $this->ticketRepository->detailParAgentEtVoyage($dateDebut, $dateFin, $identreprise);

        $agentsMap = [];
        foreach ($rawAgents as $row) {
            $id = $row['agentid'];
            if (!isset($agentsMap[$id])) {
                $agentsMap[$id] = [
                    'agentId'  => $id,
                    'nom'      => $row['nom'],
                    'prenom'   => $row['prenom'],
                    'nbtickets'=> 0,
                    'recette'  => 0.0,
                    'detail'   => [],
                ];
            }
            $agentsMap[$id]['nbtickets'] += (int)$row['nbtickets'];
            $agentsMap[$id]['recette']   += (float)$row['recette'];
            $agentsMap[$id]['detail'][]   = new CaisseDetailVoyageDto(
                codevoyage:  $row['codevoyage'],
                provenance:  $row['provenance'],
                destination: $row['destination'],
                nbtickets:   (int)$row['nbtickets'],
                recette:     round((float)$row['recette'], 2),
            );
        }

        $parAgent = array_map(
            fn($a) => new CaisseParAgentDto(
                agentId:        $a['agentId'],
                nom:            $a['nom'],
                prenom:         $a['prenom'],
                nbtickets:      $a['nbtickets'],
                recette:        round($a['recette'], 2),
                detailParVoyage: $a['detail'],
            ),
            array_values($agentsMap)
        );

        // ── Par jour ─────────────────────────────────────────────
        $rawJours = $this->ticketRepository->detailParJourEtVoyage($dateDebut, $dateFin, $identreprise);

        $joursMap = [];
        foreach ($rawJours as $row) {
            $jour = $row['jour'];
            if (!isset($joursMap[$jour])) {
                $joursMap[$jour] = [
                    'jour'     => $jour,
                    'nbtickets'=> 0,
                    'recette'  => 0.0,
                    'detail'   => [],
                ];
            }
            $joursMap[$jour]['nbtickets'] += (int)$row['nbtickets'];
            $joursMap[$jour]['recette']   += (float)$row['recette'];
            $joursMap[$jour]['detail'][]   = new CaisseDetailVoyageDto(
                codevoyage:  $row['codevoyage'],
                provenance:  $row['provenance'],
                destination: $row['destination'],
                nbtickets:   (int)$row['nbtickets'],
                recette:     round((float)$row['recette'], 2),
            );
        }

        $parJour = array_map(
            fn($j) => new CaisseParJourDto(
                jour:            $j['jour'],
                nbtickets:       $j['nbtickets'],
                recette:         round($j['recette'], 2),
                detailParVoyage: $j['detail'],
            ),
            array_values($joursMap)
        );

        return new CaisseOutput(
            totalTickets:  $totalTickets,
            recetteTotale: $recetteTotale,
            parAgent:      $parAgent,
            parJour:       $parJour,
        );
    }
}
