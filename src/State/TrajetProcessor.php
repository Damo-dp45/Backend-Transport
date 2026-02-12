<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Dto\TrajetInput;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\TarifRepository;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TrajetProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private TarifRepository $tarifRepository,
        private TrajetRepository $trajetRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var TrajetInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $trajet = $this->trajetRepository->findOneBy([
            'provenance' => $data->provenance,
            'destination' => $data->destination,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if($trajet) {
            throw new ConflictHttpException('Le trajet existe déjà pour cette entreprise');
        }

        $tarif = $this->tarifRepository->findOneBy([
            'id' => $data->tarifId,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        if(!$tarif) {
            throw new BadRequestHttpException('Tarif invalide pour cette entreprise');
        }

        if($data->provenance === $data->destination) {
            throw new BadRequestHttpException('La provenance et la destination ne doivent pas être identiques');
        }

        $trajet = new Trajet();
        $trajet
            ->setProvenance($data->provenance)
            ->setDestination($data->destination)
            ->setIdentreprise($entrepriseId)
            ->setTarif($tarif)
            ->setCreatedBy($user->getId())
            ->setCodeTrajet($this->generateCodeTrajet($entrepriseId)); /*
            ->setOrderindex(0); -- Je ne sais pas quoi mettre
        */
        // $this->em->persist($trajet); -- Pour avoir l'id du trajet mais on n'a 'process'

        /*
            $dateDebut = new \DateTimeImmutable($data->datedebut);
            $voyage = new Voyage();
            $voyage
                ->setTrajet($trajet)
                ->setCodevoyage($trajet->getCodetrajet() . '-V1')
                ->setProvenance($trajet->getProvenance())
                ->setDestination($trajet->getDestination())
                ->setDatedebut($dateDebut)
                ->setIdentreprise($entrepriseId)
                ->setCreatedBy($user->getId());
                - '->setDatefin()' au 'patch'

            if($data->carId !== null) {
                $car = $this->carRepository->findOneBy([
                    'id' => $data->carId,
                    'identreprise' => $entrepriseId,
                    'deletedAt' => null
                ]);
                if(!$car) {
                    throw new BadRequestHttpException('Car invalide');
                }
                $voyage
                    ->setCar($car)
                    ->setPlacesTotal($car->getNbrSiege());
            } else {
                $voyage->setPlacesTotal(0); -- Pour l'affecter plus tard
            }
            $voyage->setPlacesOccupees(0);
            $this->em->persist($voyage);
        */
        return $this->processor->process($trajet, $operation, $uriVariables, $context);
    }

    private function generateCodeTrajet(int $entrepriseId): string
    {
        $count = $this->em->getRepository(Trajet::class)->count([
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        return sprintf('TR-%d-%04d', $entrepriseId, $count + 1);
    }
}
