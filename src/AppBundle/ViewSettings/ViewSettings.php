<?php

namespace AppBundle\ViewSettings;

use AppKernel;
use Pug\PugSymfonyEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ViewSettings
{
    protected $kernel;
    protected $locales = [
        'fr_FR' => 'Français',
        'en_US' => 'English',
    ];

    public function __construct(AppKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    protected function getDefaultLocale()
    {
        $defaultLocale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        return isset($this->locales[$defaultLocale])
            ? $defaultLocale
            : (substr($defaultLocale, 0, 2) === 'fr'
                ? 'fr_FR'
                : 'en_US'
            );
    }

    protected function registerTextDomains()
    {
        foreach (func_get_args() as $domain) {
            bindtextdomain($domain, __DIR__ . '/../Resources/translations');
            bind_textdomain_codeset($domain, 'UTF-8');
        }
    }

    public function settings()
    {
        clearstatcache();
        $services = $this->kernel->getContainer();
        $this->registerGlobalVariables($services->get('templating.engine.pug'), $services);
        $session = $services->get('session');
        $language = $session->get('language');
        if (!$language) {
            $session->set('language', $language = $this->getDefaultLocale());
        }
        putenv('LC_ALL=' . $language);
        putenv('LANG=' . $language);
        putenv('LANGUAGE=' . $language);
        setlocale(LC_ALL, $language);
        $this->registerTextDomains('base');
    }

    protected function registerGlobalVariables(PugSymfonyEngine $pug, ContainerInterface $services)
    {
        $pug->getEngine()->share([
            'languages' => $this->locales,
        ]);
    }
}