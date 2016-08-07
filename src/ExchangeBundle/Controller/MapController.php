<?php

namespace ExchangeBundle\Controller;

use AppBundle\Entity\Map\Map;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class MapController extends Controller
{
    /**
     * @Route("/map/{id}", name="map", methods="GET")
     */
    public function getAction($id)
    {
        $map = $this->getDoctrine()->getRepository('AppBundle:Map\\Map')->findOneById($id);
        if (!$map) {
            $map = new Map('16/17;16;15;17;9;17;14;13;17;h159;r156;146;154;71;71;71;13;11;10;69;67;9;69;65;67;1;h159;h154;171;71;71;71;11;9;69;68;66;65;68;220.gif;66;65;65;67;9;109;71;71;10;11;h65;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;66;67;1;1;1;1;69;68;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;66;67;1;1;1;h65;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;r68;r69;1;1;1;h65;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;220.gif;v65;1;1;1;1;h65;220.gif;r68;r65;r65;h66;220.gif;220.gif;220.gif;220.gif;220.gif;v70;62;1;1;61;h70;r68;r69;9;1;h67;r65;r65;r65;h66;220.gif;v65;63;v60;v60;60;h67;r69;1;1;2;1;2;1;1;h67;r65;r69;1;1;1;v61;2;10;2;9;2;71;71;71;71;71;71;71;71;71;71;2;9;13;4;13;9;71;71;71;71;71;71;71;71;71;71;71;71;71;9;13;16;109;71;71;109;71;109;71;109;71;71;71;71;71;13;16;13;16;15;13;13;71;71;71;109;71;71;109;71;71;9;16;15;13;9;13;13;109;71;71;71;71;13;109;71;71;15;43;16;43;43;13;43;15;13;109;71;71;14');
            $map->setName('Leman');
            $em = $this->getDoctrine()->getManager();
            $em->persist($map);
            $em->flush();
        }

        return $this->json([
            'name' => $map->getName(),
            'width' => $map->getWidth(),
            'height' => $map->getHeight(),
            'tiles' => $map->getTiles(),
        ]);
    }

    /**
     * @Route("/move/{x}/{y}", name="move", methods="GET")
     */
    public function moveAction($x, $y)
    {
        return $this->json([
            'x' => $x,
            'y' => $y,
        ]);
    }

    /**
     * @Route("/create", name="create", methods="POST")
     */
    public function createAction()
    {
        if ($this->container->getParameter('kernel.environment') !== 'dev') {
            return;
        }
        $map = new Map($this->get('request')->request->get('tiles'));
        $map->setName($this->get('request')->request->get('name'));
        $em = $this->getDoctrine()->getManager();
        $em->persist($map);
        $em->flush();

        return $this->json([
            'width' => $map->getWidth(),
            'height' => $map->getHeight(),
            'tiles' => $map->getTiles(),
        ]);
    }
}
