<?php

namespace eZ\UnconBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('eZUnconBundle:Default:index.html.twig', array('name' => $name));
    }
}
