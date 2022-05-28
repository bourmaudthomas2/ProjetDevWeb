<?php

namespace App\Controller;

use App\Entity\Commentaire;
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
        // Récupère la liste des utilisateurs pour la vue admin
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
        //Promouvoie un utilisateur au rang d'admin
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        //On récupère l'utilisateur voulu, puis on lui ajoute le role ROLE_ADMIN
        $user->addRoles("ROLE_ADMIN");
        $entityManager->persist($user);
        $entityManager->flush($user);
        //Puis on effectue le changement dans la base.
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig',[
            'users'=> $users
        ]);
    }
    /**
     * @Route("/admin/supprimer/{id}", name="admin.supprimer")
     */
    public function suppAdmin(EntityManagerInterface $entityManager, $id)
    {
        //Même manipulation que l'ajout d'admin, mais ici, au lieu d'ajouter un role, on set les rôles avec une array vide, donc celui-ci possède le role par defaut, ROLE_USER
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $ar = array();
        $user->setRoles($ar);
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
        //Ajout d'une crypto favorite
        $user = $this->getDoctrine()->getRepository(User::class)->find($idUser);
        $crypto = $this->getDoctrine()->getRepository(Crypto::class)->find($id);
        //Récupération de l'utilisateur connecté et de la crypto, puis ajout de celle-ci à l'utilisateur
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
        //Affichage de toutes les crypto favorites de l'utilisateur
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $cryptos = $user->getCryptos();
        return $this->render('crypto/fav.html.twig', [
            'cryptos' => $cryptos
        ]);
    }
    /**
     * @Route("/user/com/{id}", name="user.commentaire")
     */
    public function commentairesUser(EntityManagerInterface $em,$id)
    {
        //Affichage des commentaires d'un utilisateur lors du clic sur le pseudo de ce dernier dans le tableau de bord admin.
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $commentaires = $user->getCommentaires();
        $nom = $user->getUsername();
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Commentaire::class);
        $totalCom = $repo->createQueryBuilder('a')
            ->where('a.user ='.$user->getId())
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        //La récupération du nombre de commentaire permet d'afficher un message si ce dernier n'en a jamais posté
        return $this->render('commentaire/list.html.twig', [
            'commentaires' => $commentaires, 'nom' => $nom, 'nb'=>$totalCom
        ]);
    }
}
