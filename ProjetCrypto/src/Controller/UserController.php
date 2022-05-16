<?php

namespace App\Controller;

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
}
