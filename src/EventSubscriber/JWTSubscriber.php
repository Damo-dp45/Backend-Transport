<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTSubscriber implements EventSubscriberInterface
{
    public function onLexikJwtAuthenticationOnJwtCreated($event): void
    {
        $data = $event->getData();
        /**
         * @var User
         */
        $user = $event->getUser();

        if(!$user instanceof User) {
            $event->setData($data);
        }
        /*
            $data['id'] = $user->getId();
            if($user->getEntreprise()) {
                $data['entrepriseId'] = $user->getEntreprise()->getId();
            }
        */
        $event->setData($data);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_jwt_created' => 'onLexikJwtAuthenticationOnJwtCreated',
        ];
    }
}
