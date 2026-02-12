<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\SiegeRepository;
use App\Repository\TicketRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class SiegeStateProvider implements ProviderInterface
{
    public function __construct(
        private SiegeRepository $siegeRepository,
        private TicketRepository $ticketRepository,
        private RequestStack $requestStack
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        // Récupérer le car depuis le filtre (IRI ou id)
        $carParam = $request->query->get('car');
        $voyageId = $request->query->get('voyage');

        if(!$carParam) {
            return [];
        }

        // Extraire l'id depuis l'IRI (/api/cars/5 → 5)
        $carId = $this->extractId($carParam);

        // Récupérer tous les sièges du car
        $sieges = $this->siegeRepository->findBy(['car' => $carId]); // On peut vérifié le 'identreprise'

        // Si un voyage est fourni, calculer les sièges occupés
        $siegesOccupes = [];
        if($voyageId) {
            $tickets = $this->ticketRepository->findBy(['voyage' => $voyageId]);
            foreach($tickets as $ticket) {
                if($ticket->getSiege()) {
                    $siegesOccupes[$ticket->getSiege()->getId()] = true;
                }
            }
        }

        // Injecter le statut sur chaque siège
        foreach($sieges as $siege) {
            $statut = isset($siegesOccupes[$siege->getId()]) ? 'OCCUPE' : 'LIBRE';
            $siege->setStatut($statut);
        }

        return $sieges;
    }

    private function extractId(string $iriOrId): int
    {
        // Si c'est un IRI (/api/cars/5), extraire le dernier segment
        if(str_contains($iriOrId, '/')) {
            $parts = explode('/', trim($iriOrId, '/'));
            return (int) end($parts);
        }
        return (int)$iriOrId;
    }

}
