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

        $debut=time();
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
            $date = date("Y/m/d H:i:s",substr($p[0],0,10));
            $crypto["graph"]["x"][]=$date;
            $crypto["graph"]["data"][]=$p[1];

            if($min>$p[1]){
                $min = $p[1];
            }
            if($max<$p[1]){
                $max = $p[1];
            }

        }

        $date = date("Y/m/d H:i:s",substr($json["prices"][0][0],0,10));

        $crypto["graph"]["max"]=$max+($max*0.1);
        $crypto["graph"]["min"]=$min-($min*0.1);

        //$crypto["graph"]["data"]=[150, 230, 224, 218, 135, 147, 260];
       // $crypto["graph"]["x"]=['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return $this->render('crypto/detail.html.twig', [
            'crypto' => $crypto
        ]);
    }
}
