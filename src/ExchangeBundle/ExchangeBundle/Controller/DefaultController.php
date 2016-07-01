<?php

namespace ExchangeBundle\ExchangeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('ExchangeBundle:Default:index.html.twig');
    }
}
