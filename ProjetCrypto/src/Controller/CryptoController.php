<?php

namespace App\Controller;

use App\DataFixtures\CryptoFixtures;
use App\Entity\Crypto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CryptoController extends AbstractController
{
    /**
     * @Route("/cryptos", name="crypto.index")
     */
    public function index(): Response
    {
        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos = $repo->findAll();

        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos
        ]);
    }

    /**
     * @Route("/crypto/load", name="crypto.load")
     */
    public function cryptoLoad(): Response
    {



        return $this->render('crypto/index.html.twig', [
            'controller_name' => 'CryptoController',
        ]);
    }

    /**
     * @Route("/crypto/detail/{id}", name="crypto.detail")
     * @return Response
     */
    public function cryptoOneById($id): Response
    {

        $res = $this->getDoctrine()->getRepository(Crypto::class)->find($id);
        $crypto['id']=$res->getId();
        $crypto['idAPI']=$res->getIdAPI();
        $crypto['nom']=$res->getNom();
        $crypto['symbole']=$res->getSymbole();
        $crypto['description']=$res->getDescription();

        if(strpos($res->getPrix(),".")){
            $crypto['prix']=number_format($res->getPrix(), 3, ",", " ");
        }else{
            $crypto['prix']=number_format($res->getPrix(), 0, "", " ");

        }
        $crypto['marketcap']=$res->getMarketcap();
        $crypto['categorie']=$res->getCategorie();
        $crypto['followers']=$res->getFollowers();
        $crypto['vote_up']=$res->getVoteUp();
        $crypto['date_creation']=$res->getDateCreation();
        $crypto['logo']=$res->getLogo();


        return $this->render('crypto/detail.html.twig', [
            'crypto' => $crypto
        ]);
    }
}
