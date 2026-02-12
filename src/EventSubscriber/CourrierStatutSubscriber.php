<?php

namespace App\EventSubscriber;

use App\Domain\Enum\CourrierStatus;
use App\Entity\Courrier;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postUpdate)]
class CourrierStatutSubscriber
{
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if(!$entity instanceof Voyage) {
            return;
        }
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $changeset = $uow->getEntityChangeSet($entity);

        if(!isset($changeset['datefin'])) {
            return;
        }
        [$oldValue, $newValue] = $changeset['datefin']; /*
            - datefin passe de null → valeur = voyage vient d'être clôturé
        */
        if($oldValue !== null || $newValue === null) {
            return;
        }

        $courriers = $em->getRepository(Courrier::class)->findBy([
            'voyage' => $entity,
            'deletedAt' => null,
        ]);
        $hasChanges = false;
        foreach($courriers as $courrier) { /*
            - On ne touche pas aux courriers déjà livrés ou annulés ni à ceux forcés manuellement au-delà de 'RECEPTIONNE'
        */
            if (in_array($courrier->getStatut(), [
                CourrierStatus::STATUT_LIVRE->value,
                CourrierStatus::STATUT_ANNULE->value,
                CourrierStatus::STATUT_PERDU->value
            ])) {
                continue;
            }
            $courrier->setStatut(CourrierStatus::STATUT_RECEPTIONNE->value);
            $em->persist($courrier);
            $hasChanges = true;
        }

        if($hasChanges) {
            $em->flush();
        }
    }
}