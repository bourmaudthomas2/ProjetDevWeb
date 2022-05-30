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
    public function index($page = 1): Response
    {
        //route pour avoir la liste des cryptos, limités a 50 cryptos par pages

        if ($page <= 0) {
            $page = 1;
        }
        $page2 = ($page - 1) * 50;

        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos = $repo->findBy(
            array(),
            array(),
            50,
            $page2
        );
//renvoie de la liste contenant les cryptos vers la vue
        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos, "pageStart" => $page, "pageEnd" => $page + 3, "sortBool" => 0
        ]);
    }

    /**
     * @Route("/cryptos/sort/{sort}&{order}/{page}", name="crypto.sort")
     */
    public function cryptosSort($sort, $order, $page = 1): Response
    {
        //on récupere les parametres de tri pour les utiliser dans la requete ensuite
        if ($sort == "date") {
            $sort = "dateCreation";
        }
        //gestion de la page
        if ($page <= 0) {
            $page = 1;
        }
        $page2 = ($page - 1) * 50;

        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos = $repo->findBy(
            array(),
            array("$sort" => "$order"),
            50,
            $page2
        );

//renvoie de la liste contenant les cryptos vers la vue
        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos, "pageStart" => $page, "pageEnd" => $page + 3, "sort" => $sort, "order" => $order, "sortBool" => 1
        ]);
    }


    /**
     * @Route("/crypto/detail/{id}", name="crypto.detail")
     * @return Response
     */
    public function cryptoOneById($id): Response
    {
        //récupération de l'id de la crypto
        $manager = $this->getDoctrine()->getManager();
        $res = $this->getDoctrine()->getRepository(Crypto::class)->find($id);
        $idAPI = $res->getIdAPI();
//mise a jour de la crypto
        $update = $this->updateCryptoOneById($idAPI, $res);


        $manager->persist($update);
        $manager->flush();

//on la recupere, apres la mise a jour
        $res = $this->getDoctrine()->getRepository(Crypto::class)->find($id);


        $crypto['id'] = $res->getId();
        $crypto['idAPI'] = $res->getIdAPI();
        $crypto['nom'] = $res->getNom();
        $crypto['symbole'] = $res->getSymbole();
        $crypto['description'] = $res->getDescription();
        $commentaires = $res->getCommentaires();
        if (strpos($res->getPrix(), ".")) {
            $crypto['prix'] = number_format($res->getPrix(), 3, ",", " ");
        } else {
            $crypto['prix'] = number_format($res->getPrix(), 0, "", " ");
        }
        $crypto['favoris'] = sizeof($res->getUsers());
        $crypto['marketcap'] = $res->getMarketcap();
        $crypto['categorie'] = $res->getCategorie();
        $crypto['followers'] = $res->getFollowers();
        $crypto['vote_up'] = $res->getVoteUp();
        $crypto['date_creation'] = $res->getDateCreation();
        $crypto['logo'] = $res->getLogo();

        //récuperation des données pour le graphe dans le détail d'une crypto.
        $ch = curl_init();
        try {
            $url = "https://api.coingecko.com/api/v3/coins/" . $crypto['idAPI'] . "/market_chart?vs_currency=eur&days=1&interval=hourly";

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

        $prices = $json["prices"];
        $min = $prices[0][1];
        $max = 0;

        $date = new \DateTime();

        $interval = date_diff($crypto["date_creation"], $date);

        if ($interval->format("%r%y") > 0) {
            $i = 0;
            //traitement des données pour les agencer et trouver les min max et la légende pour le visuel du graphe
            foreach ($prices as $p) {
                if ($i == 24) break;
                $date = date("m/d H:i", substr($p[0], 0, 10));
                $crypto["graph"]["x"][] = $date;
                $crypto["graph"]["data"][] = $p[1];

                if ($min > $p[1]) {
                    $min = $p[1];
                }
                if ($max < $p[1]) {
                    $max = $p[1];
                }
            }

            $crypto["graph"]["max"] = round($max + ($max * 0.1), 2);
            $crypto["graph"]["min"] = round($min - ($min * 0.1), 2);

        } else {
            for ($i = 0; $i < 25; $i++) {
                $crypto["graph"]["x"][$i] = 0;
                $crypto["graph"]["data"][$i] = 0;
            }
            $crypto["graph"]["max"] = 0;
            $crypto["graph"]["min"] = 0;
        }


//renvoie de la liste contenant les données pour la crypto
        return $this->render('crypto/detail.html.twig', [
            'crypto' => $crypto, 'commentaires' => $commentaires
        ]);
    }

    public function updateCryptoOneById($nameAPI, $crypto)
    {
//récuperation des données actuelle de la crypto dans le but de mettre à jour celle-ci
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
//mise à jour des données
        if ($json["market_data"]["current_price"]["eur"] == null) {
            $crypto->setPrix(0);
        } else {
            $crypto->setPrix($json["market_data"]["current_price"]["eur"]);
        }
        if ($json["market_data"]["market_cap"]["eur"] == null) {
            $crypto->setMarketcap(0);
        } else {
            $crypto->setMarketcap($json["market_data"]["market_cap"]["eur"]);
        }
        if ($json["community_data"]["twitter_followers"] == null) {
            $crypto->setFollowers(0);
        } else {
            $crypto->setFollowers($json["community_data"]["twitter_followers"]);
        }

        if (array_key_exists("categories", $json)) {
            if (sizeof($json["categories"]) > 0) {
                if ($json["categories"][0] == null) {
                    $crypto->setCategorie("");
                } else {
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

        if ($page <= 0) {
            $page = 1;
        }
        $page2 = ($page - 1) * 50;

        $cat = str_replace("_", ".", $cat);

        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $conn = $this->getDoctrine()->getConnection();

        $sql = "select distinct categorie from Crypto where categorie != '';";
        $stmt = $conn->prepare($sql);

        $stmt->execute();
        $categories = $stmt->fetchAll();

//récupération des catégories pour la liste déroulante
        foreach ($categories as $category => $cate) {
            //var_dump($cate["categorie"]);
            $categories[$category]["value"] = str_replace(".", "_", $cate["categorie"]);
            $categories[$category]["nom"] = $cate["categorie"];
            if ($cate["categorie"] == $cat) {
                $categories[$category]["selected"] = 'selected="true"';
            } else {
                $categories[$category]["selected"] = "";
            }

        }
//récupération des cryptos, triés par catégorie
        $cryptos = $repo->findBy(
            array("categorie" => $cat),
            array(),
            50,
            $page2
        );

//renvoie de la liste contenant les cryptos vers la vue
        return $this->render('crypto/categorie.html.twig', [
            'cryptos' => $cryptos, "cat" => $cat, "categories" => $categories, "pageStart" => $page, "pageEnd" => $page + 3
        ]);
    }

    /**
     * @Route("/cryptos/recherche/{id}", name="crypto.recherche")
     * @return Response
     */
    public function cryptosRecherche($id = 1, Request $request): Response
    {
//création du formulaire pour la recherche avancée
        $form = $this->createForm(CryptoType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();


            $conn = $this->getDoctrine()->getConnection();


            $name = $task["name"];
            $price1 = $task["price1"];
            $price2 = $task["price2"];
            $marketcap1 = $task["marketcap1"];
            $marketcap2 = $task["marketcap2"];
            $category = $task["category"];
            $dateCreation1 = $task["dateCreation1"];
            $dateCreation2 = $task["dateCreation2"];

            $followers1 = $task["followers1"];
            $followers2 = $task["followers2"];
            $favoris1 = $task["favoris1"];
            $favoris2 = $task["favoris2"];

            //création de la requete qui va venir chercher les cryptos selon les clés de recherches envoyée.
            if (!is_null($favoris1)) {
                $sql = "select c.*, count(u.user_id) as nbFav from Crypto c left outer join user_crypto u on u.crypto_id = c.id where 1=1 ";
            } else {
                $sql = "select * from Crypto c where 1=1 ";

            }
            if (!is_null($name)) {
                $sql .= "and nom like '%$name%' ";
            }
            if (!is_null($category)) {
                $sql .= "and categorie like '%$category%' ";
            }

            if (!is_null($price1)) {
                if (!is_null($price2)) {
                    $price1 = str_replace(",", ".", $price1);
                    $price2 = str_replace(",", ".", $price2);

                    $sql .= "and prix between '$price1' and '$price2' ";
                } else {
                    $sql .= "and prix = '$price1' ";
                }
            }
            if (!is_null($marketcap1)) {
                if (!is_null($marketcap2)) {
                    $sql .= "and marketcap between '$marketcap1' and '$marketcap2' ";
                } else {
                    $sql .= "and marketcap = '$marketcap1' ";
                }
            }
            if (!is_null($dateCreation1)) {
                if (!is_null($dateCreation1)) {
                    $dateCreation1 = $dateCreation1->format('Y-m-d H:i:s.u');
                    $dateCreation2 = $dateCreation2->format('Y-m-d H:i:s.u');

                    $sql .= "and date_creation between '$dateCreation1' and '$dateCreation2' ";
                } else {
                    $sql .= "and date_creation = '$dateCreation1' ";
                }

            }
            if (!is_null($followers1)) {
                if (!is_null($followers1)) {
                    $sql .= "and followers between '$followers1' and '$followers2' ";
                } else {
                    $sql .= "and followers = '$followers1' ";
                }
            }
            if (!is_null($favoris1)) {
                $sql .= "group by u.crypto_id ";
            }

            if (!is_null($favoris1)) {
                if (!is_null($favoris2)) {
                    $sql .= "having count(u.user_id) between '$favoris1' and '$favoris2' ";
                } else {
                    $sql .= "having count(u.user_id) = '$favoris1' ";
                }
            }
            $sql .= ";";

            if ($sql == "select * from Crypto c left outer join user_crypto u on u.crypto_id = c.id where 1=1 group by u.crypto_id;") {
                $res = array();
            } else {
                $stmt = $conn->prepare($sql);

                $stmt->execute();
                $res = $stmt->fetchAll();

            }
//retour vers la vue de résultat
            return $this->render('crypto/resultat.html.twig', [
                "cryptos" => $res
            ]);
        }

        return $this->render('crypto/recherche.html.twig', [
            'searchForm' => $form->createView(),
        ]);
    }


    /**
     * @Route("/cryptos/recherche_avancer/null", name="crypto.recherche_avancee")
     * @return Response
     */
    public function cryptosRechercheAvancee(): Response
    {

        $ch = curl_init();
        try {
            $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&order=market_cap_desc&per_page=20&page=1";

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
        $cryptos = array();
        $cryptosPrices = array();
        foreach ($json as $j) {
            $cryptos[] = $j["id"];
            $cryptosPrices[$j["id"]] = $j["current_price"];
        }
        foreach ($cryptos as $c) {
            $ch = curl_init();
            try {
                $url = "https://api.coingecko.com/api/v3/coins/$c/market_chart?vs_currency=eur&days=365&interval=daily";

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

            $prices = $json["prices"];
            $min = 999999999999999999;
            $max = -1;
            foreach ($prices as $p) {
                if ($min > $p[1]) {
                    $min = $p[1];
                }
                if ($max < $p[1]) {
                    $max = $p[1];
                }

            }
            $moy = ($min + $max) / 2;

            if ($cryptosPrices[$c] > $moy) {
                $valsCryptos[] = $c;
                $vals[$c] = array("id" => $c, "min" => $min, "max" => $max, "moy" => round($moy, 2) . " €", "actual" => $cryptosPrices[$c]);
            }


        }
        $repo = $this->getDoctrine()->getRepository(Crypto::class);
        $cryptos2 = $repo->findBy(
            array("idAPI" => $valsCryptos),
            array()
        );
        // var_dump($cryptos2);

        return $this->render('crypto/recherche_avance.html.twig', [
            "cryptos" => $cryptos2, "vals" => $vals
        ]);
    }

    /**
     * @Route("/cryptos/cryptoMarketcap/null", name="crypto.cryptoMarketcap")
     * @return Response
     */
    public function cryptosByMarketcap(): Response
    {



        $repo = $this->getDoctrine()->getRepository(Crypto::class);

        $marketcap2 = $repo->cryptoByMarketcap();


        return $this->render('crypto/marketcap.html.twig', [
            "marketcap" => $marketcap2
        ]);
    }
    /**
     * @Route("/cryptos/marketcap/{id}", name="crypto.cryptoMarketcapDetail")
     * @return Response
     */
    public function cryptosByMarketcapDetail($id): Response
    {


        $repo = $this->getDoctrine()->getRepository(Crypto::class);

        $cryptos = $repo->cryptoByMarketcapDetail($id);

        return $this->render('crypto/listmarketcap.html.twig', [
            "cryptos"=>$cryptos
        ]);
    }
}
