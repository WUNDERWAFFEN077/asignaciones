<?php

namespace AJUserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AJUserBundle:Default:index.html.twig');
    }
}
