<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Dto\AffectcarInput;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AffectcarProcessor implements ProcessorInterface
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
        /** @var AffectcarInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $voyage = $this->em->getRepository(Voyage::class)->findOneBy([
            'id' => $uriVariables['id'],
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if(!$voyage) {
            throw new BadRequestHttpException('Voyage introuvable');
        }

        if($voyage->getCar()) {
            throw new BadRequestHttpException('Un car est déjà affecté à ce voyage');
        }

        $car = $data->car;
        $voyage->setCar($car);
        $voyage->setPlacesTotal($car->getNbrsiege());

        return $this->processor->process($voyage, $operation, $uriVariables, $context);
    }
}
