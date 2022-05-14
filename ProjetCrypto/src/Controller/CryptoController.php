<?php

namespace App\Controller;

use App\DataFixtures\CryptoFixtures;
use App\Entity\Crypto;
use DateTime;
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
        $cryptos = $repo->findBy(
            array(),
            array(),
            50,
            0
        );

        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos
        ]);
    }
    /**
     * @Route("/cryptos/sort/{sort}&{order}", name="crypto.sort")
     */
    public function cryptosSort($sort,$order): Response
    {
        if($sort == "date"){
            $sort = "dateCreation";
        }
        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos = $repo->findBy(
            array(),
            array("$sort" => "$order")
        );

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
        $manager = $this->getDoctrine()->getManager();
        $res = $this->getDoctrine()->getRepository(Crypto::class)->find($id);
        $idAPI=$res->getIdAPI();

        $update = $this->updateCryptoOneById($idAPI, $res);


        $manager->persist($update);
        $manager->flush();



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

        $ch = curl_init();
        try {
            $url = "https://api.coingecko.com/api/v3/coins/".$crypto['idAPI']."/market_chart?vs_currency=eur&days=1&interval=hourly";

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo curl_error($ch);
                die();
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == intval(200)) {
                //echo $response;
            } else {
                echo "Ressource introuvable : " . $http_code;
            }
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            curl_close($ch);
        }

        $json = json_decode($response, 1);
        //var_dump($json);
        $prices = $json["prices"];
        $min = $prices[0][1];
        $max = 0;
        foreach ($prices as $p){
            $date = date("m/d H:i",substr($p[0],0,10));
            $crypto["graph"]["x"][]=$date;
            $crypto["graph"]["data"][]=$p[1];

            if($min>$p[1]){
                $min = $p[1];
            }
            if($max<$p[1]){
                $max = $p[1];
            }

        }
        $crypto["graph"]["max"]=round($max+($max*0.1),2);
        $crypto["graph"]["min"]=round($min-($min*0.1),2);



        return $this->render('crypto/detail.html.twig', [
            'crypto' => $crypto
        ]);
    }

    public function updateCryptoOneById($nameAPI, $crypto){

        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, "https://api.coingecko.com/api/v3/coins/$nameAPI");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo curl_error($ch);
                die();
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == intval(200)) {
                //echo $response;
            } else {
                echo "Ressource introuvable : " . $http_code;
            }
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            curl_close($ch);
        }

        $json = json_decode($response, 1);

        if($json["market_data"]["current_price"]["eur"] == null){
            $crypto->setPrix(0);
        }else{
            $crypto->setPrix($json["market_data"]["current_price"]["eur"]);
        }
        if($json["market_data"]["market_cap"]["eur"] == null){
            $crypto->setMarketcap(0);
        }else{
            $crypto->setMarketcap($json["market_data"]["market_cap"]["eur"]);
        }
        if($json["community_data"]["twitter_followers"] == null){
            $crypto->setFollowers(0);
        }else{
            $crypto->setFollowers($json["community_data"]["twitter_followers"]);
        }
        if($json["categories"][0] == null){
            $crypto->setCategorie("");
        }else{
            $crypto->setCategorie($json["categories"][0]);
        }
        return $crypto;
    }


}
