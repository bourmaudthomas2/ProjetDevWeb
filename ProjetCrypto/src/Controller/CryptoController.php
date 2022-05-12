<?php

namespace App\Controller;

use App\DataFixtures\CryptoFixtures;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CryptoController extends AbstractController
{
    /**
     * @Route("/crypto", name="app_crypto")
     */
    public function index(): Response
    {
        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $crypto = $repo->findAll();
        return $this->render('crypto/index.html.twig', [
            'controller_name' => 'CryptoController', $crypto
        ]);
    }

    /**
     * @Route("/crypto/load", name="app_crypto_load")
     */
    public function cryptoLoad(): Response
    {


        return $this->render('crypto/index.html.twig', [
            'controller_name' => 'CryptoController',
        ]);

    }
}
