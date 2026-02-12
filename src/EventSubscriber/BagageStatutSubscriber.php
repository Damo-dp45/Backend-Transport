<?php

namespace App\EventSubscriber;

use App\Domain\Enum\BagageStatus;
use App\Entity\Bagage;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postUpdate)] /*
    - Le 'postUpdate' au lieu de 'preUpdate' car avec lui le flush est encore en cours et les 'persist' sur d'autres entités sont ignorés ou causent des comportements imprévisibles or en 'postUpdate' le flush principal est terminé et on peut en déclencher un nouveau proprement
*/
class BagageStatutSubscriber
{
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if(!$entity instanceof Voyage) {
            return;
        }
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork(); /*
            - En 'postUpdate' on ne peut plus utiliser 'hasChangedField', on récupère le 'changeset' depuis le 'UnitOfWork'
        */
        $changeset = $uow->getEntityChangeSet($entity);
        if(!isset($changeset['datefin'])) {
            return;
        }
        [$oldValue, $newValue] = $changeset['datefin']; /*
            - datefin passe de null à une valeur → voyage vient d'être clôturé
        */
        if($oldValue !== null || $newValue === null) {
            return;
        }

        $bagages = $em->getRepository(Bagage::class)->findBy([
            'voyage' => $entity,
            'deletedAt' => null
        ]);

        $hasChanges = false;
        foreach($bagages as $bagage) {
            if(!in_array($bagage->getStatut(), [
                BagageStatus::STATUT_LIVRE->value,
                BagageStatus::STATUT_PERDU->value
            ])) {
                $bagage->setStatut(BagageStatus::STATUT_LIVRE->value);
                $em->persist($bagage);
                $hasChanges = true;
            }
        }

        if($hasChanges) { /*
            - On sépare le flush car on est déjà sorti du flush principal
        */
            $em->flush();
        }
    }
}
