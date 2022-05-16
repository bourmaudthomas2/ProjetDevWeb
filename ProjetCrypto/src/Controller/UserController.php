<?php

namespace App\Controller;

use App\Entity\Crypto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="app_user")
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    public function admin()
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig',[
            'users'=> $users
        ]);
    }
    /**
     * @Route("/admin/ajouter/{id}", name="admin.ajouter")
     */
    public function addAdmin(EntityManagerInterface $entityManager, $id)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $user->addRoles("ROLE_ADMIN");
        $entityManager->persist($user);
        $entityManager->flush($user);

        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig',[
            'users'=> $users
        ]);
    }
    /**
     * @Route("/favori/ajouter/{idUser}/{id}", name="favori.ajouter")
     */
    public function addFav(EntityManagerInterface $entityManager, $idUser,$id)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($idUser);
        $crypto = $this->getDoctrine()->getRepository(Crypto::class)->find($id);
        $user->addCrypto($crypto);
        $entityManager->persist($user);
        $entityManager->flush($user);
        return $this->redirect("localhost:8000/cryptos");
    }
    /**
     * @Route("/user/fav/{id}", name="favori.index")
     */
    public function fav(EntityManagerInterface $entityManager, $id)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $cryptos = $user->getCryptos();
        return $this->render('crypto/index.html.twig', [
            'cryptos' => $cryptos
        ]);
    }
}
