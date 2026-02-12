<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VoyageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Voyage $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        if($operation instanceof Post) {
            if($data->getProvenance() === $data->getDestination()) {
                throw new BadRequestHttpException('La provenance et la destination ne peuvent pas être identiques');
            }

            $trajet = $data->getTrajet();

            $data
                ->setIdentreprise($entrepriseId)
                ->setCreatedBy($user->getId())
                ->setDestination($trajet->getDestination()); // !!

            $code = $this->em->getRepository(Voyage::class)->count([
                'trajet' => $data->getTrajet(),
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]) + 1;
            $data
                ->setPlacesOccupees(0)
                ->setCodevoyage($data->getTrajet()->getCodetrajet() . '-V' . $code); /*
                - On peut avoir un problème de concurrence '2 créations en même temps' donc à améliorer
            */
            if($data->getCar()) {
                $this->getCar($data);
            } else {
                $data->setPlacesTotal(0);
            }
        }

        if($operation instanceof Patch) {
            $original = $this->em->getUnitOfWork()->getOriginalEntityData($data); /*
                - Pour récupérer l'état original de l'objet depuis la base de données avant les modifications sinon '$data->getDatefin()' nous donne l'état avant modification
            */
            if(!empty($original['datefin'])) {
                throw new BadRequestHttpException('Ce voyage est déjà clôturé et ne peut plus être modifié');
            }

            $data->setUpdatedBy($user->getId());

            if($data->getDatefin() && $data->getDatefin() <= $data->getDatedebut()) {
                throw new BadRequestHttpException('La date de fin doit être supérieure à la date de départ');
            }

            if($data->getCar()) {
                $this->getCar($data);
            }
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function getCar(Voyage $data)
    {
        if($data->getCar()) { /*
            - On vérifie si le car est déjà utilisé sur un autre voyage au même moment
        */
            $existingCarForVoyage = $this->em->getRepository(Voyage::class)
                ->createQueryBuilder('v')
                ->where('v.car = :car')
                ->andWhere('v.id != :currentId OR :currentId IS NULL')
                ->andWhere('v.datefin IS NULL')
                ->andWhere('v.deletedAt IS NULL')
                ->setParameter('car', $data->getCar())
                ->setParameter('currentId', $data->getId())
                ->getQuery()
                ->getOneOrNullResult()
            ;
            if($existingCarForVoyage) {
                throw new BadRequestHttpException(
                    'Ce véhicule est déjà utilisé sur un voyage en cours — clôturez-le avant de l\'affecter à un nouveau voyage'
                );
            }

            $places = $data->getCar()->getNbrSiege();
            if($data->getPlacesOccupees() > $places) { // On vérifie que les places déjà occupées ne dépassent celui du nouveau car en cas de 'patch'
                throw new BadRequestHttpException(
                    'Impossible de changer de Car : les places déjà occupées dépassent la capacité du nouveau véhicule'
                );
            }
            $data->setPlacesTotal($places);
        }
    }
}
