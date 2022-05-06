<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Stage;


class StageController extends AbstractController
{
    /**
     * @Route("/stage", name="app_stage")
     */
    public function list() : Response
    {
        $stages = $this->getDoctrine()->getRepository(Stage::class)->findAll();
        return $this->render('stage/list.html.twig', [
            'stages' => $stages,
        ]);
    }
}
