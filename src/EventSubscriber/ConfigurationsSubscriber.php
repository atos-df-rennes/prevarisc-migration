<?php

namespace App\EventSubscriber;

use App\Repository\ConfigurationsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ConfigurationsSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {

    }

    public function onKernelRequest(RequestEvent $event)
    {

    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
