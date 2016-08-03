<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        textdomain("base");

        return $this->render('AppBundle::index.pug', [
            'title' => _("Leman Dragon"),
        ]);
    }

    /**
     * @Route("/language/{language}", name="language")
     */
    public function languageAction($language, Request $request)
    {
        $request->getSession()->set('language', $language);

        return $this->redirectToRoute('homepage');
    }
}
