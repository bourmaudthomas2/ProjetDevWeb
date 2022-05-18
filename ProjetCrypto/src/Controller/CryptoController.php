<?php

namespace App\Controller;

use App\Entity\Crypto;
use App\Form\CryptoType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CryptoController extends AbstractController
{
    /**
     * @Route("/cryptos/{page}", name="crypto.index")
     */
    public function index($page=1): Response
    {
        //var_dump($page);
        if($page<=0){
            $page=1;
        }
        $page2= ($page-1)*50;

        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos = $repo->findBy(
            array(),
            array(),
            50,
            $page2
        );

        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos, "pageStart" => $page, "pageEnd" => $page+3, "sortBool"=>0
        ]);
    }
    /**
     * @Route("/cryptos/sort/{sort}&{order}/{page}", name="crypto.sort")
     */
    public function cryptosSort($sort,$order, $page=1): Response
    {
        if($sort == "date"){
            $sort = "dateCreation";
        }
        if($page<=0){
            $page=1;
        }
        $page2= ($page-1)*50;

        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos = $repo->findBy(
            array(),
            array("$sort" => "$order"),
            50,
            $page2
        );

        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos, "pageStart" => $page, "pageEnd" => $page+3, "sort"=>$sort, "order"=>$order, "sortBool"=>1
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
        $commentaires = $res->getCommentaires();
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

        $i=0;
        foreach ($prices as $p){
            if($i == 24) break;
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
            'crypto' => $crypto, 'commentaires' => $commentaires
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

        if(array_key_exists("categories", $json)){
            if(sizeof($json["categories"])>0){
                if($json["categories"][0] == null){
                    $crypto->setCategorie("");
                }else{
                    $crypto->setCategorie($json["categories"][0]);
                }
            }
        }

        return $crypto;
    }

    /**
     * @Route("/cryptos/categorie/{cat}/{page}", name="crypto.categorie")
     * @return Response
     */
    public function cryptosCategorie($cat = "Cryptocurrency", $page = 1): Response
    {

        if($page<=0){
            $page=1;
        }
        $page2= ($page-1)*50;

        $cat = str_replace("_",".", $cat);

        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $conn = $this->getDoctrine()->getConnection();

        $sql = "select distinct categorie from Crypto where categorie != '';";
        $stmt = $conn->prepare($sql);

        $stmt->execute();
        $categories=  $stmt->fetchAll();


        foreach ($categories as $category=>$cate){
          //var_dump($cate["categorie"]);
            $categories[$category]["value"]=str_replace(".","_", $cate["categorie"]);
            $categories[$category]["nom"]=$cate["categorie"];
            if($cate["categorie"]==$cat){
                $categories[$category]["selected"]='selected="true"';
            }else{
                $categories[$category]["selected"]="";
            }

        }

        $cryptos = $repo->findBy(
            array("categorie"=>$cat),
            array(),
            50,
            $page2
        );

//var_dump($cryptos[1]);


        return $this->render('crypto/categorie.html.twig', [
            'cryptos' => $cryptos, "cat"=>$cat, "categories"=>$categories, "pageStart" => $page, "pageEnd" => $page+3
        ]);
    }

    /**
     * @Route("/cryptos/recherche/{id}", name="crypto.recherche")
     * @return Response
     */
    public function cryptosRecherche($id=1, Request $request): Response
    {

        $form = $this->createForm(CryptoType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();




            $conn = $this->getDoctrine()->getConnection();


            $name=$task["name"];
            $price1=$task["price1"];
            $price2=$task["price2"];
            $marketcap1=$task["marketcap1"];
            $marketcap2=$task["marketcap2"];
            $category=$task["category"];
            $dateCreation1=$task["dateCreation1"];
            $dateCreation2=$task["dateCreation2"];

            $sql = "select * from Crypto where 1=1 ";

            if(!is_null($name)){
                $sql.= "and nom like '%$name%'";
            }
            if(!is_null($category)){
                $sql.= "and categorie like '%$category%'";
            }
            if(!is_null($price1)){
                $sql.= " and prix between '$price1' and '$price2'";
            }
            if(!is_null($marketcap1)){
                $sql.= "and marketcap between '$marketcap1' and '$marketcap2'";
            }
            if(!is_null($dateCreation1)){
                $sql.= "and date_creation between '$dateCreation1' and '$dateCreation2'";
            }
            $sql.= ";";


            if($sql=="select * from Crypto where 1=1 ;"){
                $res=array();
            }else{
                $stmt = $conn->prepare($sql);

                $stmt->execute();
                $res=  $stmt->fetchAll();

            }

            // ... perform some action, such as saving the task to the database

            return $this->render('crypto/resultat.html.twig', [
               "cryptos"=> $res
            ]);
        }

        return $this->render('crypto/recherche.html.twig', [
            'searchForm' => $form->createView(),
        ]);
    }


}
