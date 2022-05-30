<?php

namespace App\Repository;

use App\Entity\Crypto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Crypto>
 *
 * @method Crypto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Crypto|null findOneBy(array $criteria, array $orderBy = null)
 * @method Crypto[]    findAll()
 * @method Crypto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CryptoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Crypto::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Crypto $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Crypto $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    // /**
    //  * @return Crypto[] Returns an array of Crypto objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Crypto
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function cryptoByMarketcap(){
        $marketcap = array("50m" => 0, "500m" => 0, "1b" => 0, "+1b" => 0);

        $qb = $this->createQueryBuilder('c')
            ->select('c.id, c.marketcap');
        $data = $qb->getQuery();
        $data = $data->execute();

        foreach ($data as $d) {

            $cap = $d["marketcap"];

            switch ($cap) {
                case $cap < 50000000 :
                    $marketcap["50m"] = $marketcap["50m"] + 1;
                    break;
                case $cap < 500000000 :
                    $marketcap["500m"] = $marketcap["500m"] + 1;
                    break;
                case $cap < 1000000000 :
                    $marketcap["1b"] = $marketcap["1b"] + 1;
                    break;
                case $cap > 1000000000:
                    $marketcap["+1b"] = $marketcap["+1b"] + 1;
                    break;

            }
        }
        $marketcap2 = array(
            ["classe" => "< 50 millions", "nb" => $marketcap["50m"], "id"=>0],
            ["classe" => "< 500 millions", "nb" => $marketcap["500m"],"id"=>1],
            ["classe" => "< 1 milliard", "nb" => $marketcap["1b"],"id"=>2],
            ["classe" => "> 1 milliard", "nb" => $marketcap["+1b"],"id"=>3]
        );

        return $marketcap2;
    }

    public function cryptoByMarketcapDetail($id){

        switch($id){
            case 0:$sup=50000000;$inf=0;break;
            case 1:$sup=500000000;$inf=50000000;break;
            case 2:$sup=1000000000;$inf=500000000;break;
            case 3:$sup=0;$inf=1000000000;break;
        }

        if($sup == 0){
            $qb = $this->createQueryBuilder('c')
                ->where("c.marketcap > $inf");

            $data = $qb->getQuery();
            $data = $data->execute();
        }else{
            $qb = $this->createQueryBuilder('c')
                ->where("c.marketcap > $inf")
                ->andWhere("c.marketcap < $sup");

            $data = $qb->getQuery();

            $data = $data->execute();

        }

        return $data;
    }
}
