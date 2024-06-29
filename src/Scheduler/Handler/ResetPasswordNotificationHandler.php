<?php

namespace App\Scheduler\Handler;

use DateTime;
use DateInterval;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Mercure\Update;
use App\Repository\PersonalRepository;
use Symfony\Component\Mercure\HubInterface;
use App\Scheduler\Message\ResetPasswordNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ResetPasswordNotificationHandler
{

    public function __construct(private HubInterface $hub, private CacheItemPoolInterface $cacheItemPool, private PersonalRepository $personalRepository)
    {
    }

    public function __invoke(ResetPasswordNotification $message)
    {

        // dd($this->connectedPersonalRepository->findAll());

        //dd($this->security->getUser());

        $item = $this->cacheItemPool->getItem('lastIdentifier');
        $emailConnectedUser  = $item->get('lastIdentifier');

        //dd($item);
        // dd($item->get('#value'));
        $user = $this->personalRepository->findOneBy(
            [
                'email' => $emailConnectedUser,
            ]
        );
        // dd($user);

        if ($user) {
            $intervals = ['P0D', 'P83D', 'P85D', 'P87D', 'P90D'];
            $lastUpdatedPassword = $user->getLastUpdatedPassword();
            foreach ($intervals as $interval) {
                $today = new DateTime('2024-06-30');
                $dateToCheck = clone $lastUpdatedPassword;
                $dateToCheck->add(new DateInterval($interval));
                if ($dateToCheck->format('Y-m-d') === $today->format('Y-m-d')) {
                    $update = new Update(
                        'notifPasswordReset',
                        'Veuillez modifier votre mot de passe'
                    );
                    $this->hub->publish($update);
                }
            }
        } else {
            throw new \ErrorException("Aucun utilisateur connecté");
        }
    }
}
