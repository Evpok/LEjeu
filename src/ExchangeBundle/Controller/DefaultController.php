<?php

namespace ExchangeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="list", methods="GET")
     */
    public function indexAction()
    {
        return $this->json([
            'hello' => 'world',
        ]);
    }
}
