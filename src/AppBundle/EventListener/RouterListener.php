<?php

namespace AppBundle\EventListener;

use AppBundle\Controller\TokenAuthenticatedController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Pug\Filter\Minify;

class RouterListener
{
    public function onKernelController(FilterControllerEvent $event)
    {
        putenv('LC_ALL=fr_FR');
        setlocale(LC_ALL, 'fr_FR');
        bindtextdomain("base", __DIR__ . '/../Resources/translations');

        var_dump($this->get('templating.engine.pug')->getOption('singleQuote'));
        $this->get('templating.engine.pug')->setOption('singleQuote', false);
        if ($this->container->getParameter('kernel.environment') === 'dev') {
            Minify::devMode();
        }
        Minify::setAssetDirectory(__DIR__ . '/../Resources/assets');
        Minify::setOutputDirectory(__DIR__ . '/../../../web');
    }
}