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


        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, "https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&order=market_cap_desc&per_page=15000&page=1&sparkline=false");
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
        foreach ($json as $j){
            $crypto = new Crypto();
            $crypto->setNom($j["name"]);
            $crypto->setSymbole($j["symbol"]);
            $crypto->setPrix($j["current_price"]);
            $crypto->setDescription("");
            $crypto->setMarketcap($j["market_cap"]);
            $crypto->setCategorie("");
            $crypto->setFollowers(0);
            $crypto->setVoteUp(0);
            $crypto->setLogo($j["image"]);

            $date = new DateTime(substr($j["atl_date"],0,10));

            $crypto->setDateCreation($date);

            $manager->persist($crypto);
        }
        $manager->flush();

    }
}