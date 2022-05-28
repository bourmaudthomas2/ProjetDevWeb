<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController
{
    /**
     * @Route("/change-locale/{locale}", name="change_locale")
     */
    public function changeLocale($locale, Request $request)
    {
        //Permet de modifier la variable locale, qui permet de connaitre la langue actuelle
        $request->getSession()->set('_locale',$locale);
        return $this->redirect($request->headers->get('referer'));
    }
}
