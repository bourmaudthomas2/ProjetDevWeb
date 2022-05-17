<?php

namespace App\DataFixtures;

use App\Entity\Crypto;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class CryptoFixtures extends Fixture
{


    public function load(ObjectManager $manager)
    {
        ini_set('memory_limit', '-1');
        echo "fixtures cryptos\n";
        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, "https://api.coingecko.com/api/v3/global");
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

        $cryptos = array();

        $global = json_decode($response, 1);
        $global = $global["data"]["active_cryptocurrencies"];
        //echo "total = $global\n";
        $nbPage = floor($global / 250) + 2;

        for ($i = 1; $i < $nbPage; $i++) {
            //echo "page $i\n";
            $ch = curl_init();
            try {
                curl_setopt($ch, CURLOPT_URL, "https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&order=market_cap_desc&per_page=250&page=$i&sparkline=false");
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


            foreach ($json as $j) {

                $cryptos[$j["id"]]["nom"] = $j["name"];
                $cryptos[$j["id"]]["idAPI"] = $j["id"];
                $cryptos[$j["id"]]["symbole"] = $j["symbol"];
                if ($j["current_price"] == null) {
                    $cryptos[$j["id"]]["prix"] = 0;
                } else {
                    $cryptos[$j["id"]]["prix"] = $j["current_price"];
                }
                $cryptos[$j["id"]]["description"] = "";
                if ($j["market_cap"] == null) {
                    $cryptos[$j["id"]]["market_cap"] = 0;
                } else {
                    $cryptos[$j["id"]]["market_cap"] = $j["market_cap"];
                }

                $cryptos[$j["id"]]["followers"] = 0;
                $cryptos[$j["id"]]["voteUp"] = 0;
                $cryptos[$j["id"]]["logo"] = $j["image"];

                $date = new DateTime(substr($j["atl_date"], 0, 10));
                $cryptos[$j["id"]]["dateCreation"] = $date;
                $cryptos[$j["id"]]["categorie"] = "";

            }

            sleep(1);
        }

        echo "fixtures categorie\n";

        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, "https://api.coingecko.com/api/v3/coins/categories/list");
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

        $listCategorie = json_decode($response, 1);

        $index = 1;
        $i = 1;
        foreach ($listCategorie as $categorie) {
            $stop = false;
            $i = 1;
            if (!str_contains($categorie["category_id"], "layer")) {
                if (!str_contains($categorie["category_id"], "us-election-2020")) {
                    while (!$stop) {
                        $ch = curl_init();
                        echo ($categorie["category_id"] . ' ' . $i."\n");
                        try {
                            curl_setopt($ch, CURLOPT_URL, "https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&category=" . $categorie["category_id"] . "&order=market_cap_desc&per_page=50&page=$i&sparkline=false");
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

                        $cryptosByCategorie = json_decode($response, 1);

                        foreach ($cryptosByCategorie as $cryp) {


                            foreach ($cryptos as $cry => $crypt) {

                                if ($cry == $cryp["id"]) {
                                    if ($cryptos[$cryp["id"]]["categorie"] == "") {

                                        //var_dump($index. ' ' .$cryp["id"]);
                                        $index++;
                                        $cryptos[$cry]["categorie"] = $categorie["category_id"];

                                    }
                                }
                            }

                        }

                        if (sizeof($cryptosByCategorie) < 50) $stop = true;
                        sleep(1);
                        $i++;
                    }
                }
            }
        }
        echo "Traitement\n";
        foreach ($cryptos as $cry) {

            $crypto = new Crypto();

            $crypto->setNom($cry["nom"]);
            $crypto->setIdAPI($cry["idAPI"]);
            $crypto->setSymbole($cry["symbole"]);
            $crypto->setPrix($cry["prix"]);
            $crypto->setDescription($cry["description"]);
            $crypto->setMarketcap($cry["market_cap"]);
            $crypto->setFollowers($cry["followers"]);
            $crypto->setVoteUp($cry["voteUp"]);
            $crypto->setDateCreation($cry["dateCreation"]);
            $crypto->setCategorie($cry["categorie"]);
            $crypto->setLogo($cry["logo"]);
            $manager->persist($crypto);
            $manager->flush();
        }

    }
}