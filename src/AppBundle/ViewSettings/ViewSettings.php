<?php

namespace AppBundle\ViewSettings;

use Pug\Filter\Minify;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ViewSettings
{
    protected $kernel;

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }

    public function settings()
    {
        $this->localeSetting();
        $this->minifySetting();
    }

    protected function localeSetting()
    {
        $session = new Session();
        $language = $session->get('language', 'fr_FR');
        putenv('LC_ALL=' . $language);
        setlocale(LC_ALL, $language);
        foreach ([
            'base',
        ] as $domain) {
            bindtextdomain($domain, __DIR__ . '/../Resources/translations');
        }
    }

    protected function minifySetting()
    {
        $container = $this->kernel->getContainer();
        $pug = $container->get('templating.engine.pug');
        $pug->setOption('singleQuote', false);
        if ($container->getParameter('kernel.environment') === 'dev') {
            $pug
                ->setOption('cache', false)
                ->setCustomOption('environnement', 'dev')
            ;
        }
        $pug->setCustomOptions([
            'assetDirectory' => __DIR__ . '/../Resources/assets',
            'outputDirectory' => __DIR__ . '/../../../web',
        ]);
    }
}