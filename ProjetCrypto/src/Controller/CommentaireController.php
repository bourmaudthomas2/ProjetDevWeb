<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commentaire;
use App\Entity\User;
use App\Entity\Crypto;
use App\Form\CommentaireType;

class CommentaireController extends AbstractController
{
    /**
     * @Route("/commentaire", name="app_commentaire")
     */
    public function index(): Response
    {
        return $this->render('commentaire/index.html.twig', [
            'controller_name' => 'CommentaireController',
        ]);
    }
    /**
     *
     * @Route("/add/comm/{id}", name="ajoutComm")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return RedirectResponse|Response
     */
    public function ajoutComm(Request $request, EntityManagerInterface $em, $id):Response
    {
        $commentaire = new Commentaire();
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('username' => $this->getUser()->getUsername()));
        $crypto = $this->getDoctrine()->getRepository(Crypto::class)->findOneBy(array('id'=>$id));
        $commentaire->setUser($user);
        $commentaire->setCrypto($crypto);
        $commentaire->setDate(new \DateTime('now'));
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($commentaire);
            $user->addCommentaire($commentaire);
            $crypto->addCommentaire($commentaire);

            $em->flush();
            return $this->redirectToRoute('crypto.index');

        }
        return $this->render('commentaire/index.html.twig', [
            'form' => $form->createView(), ]);

    }
    /**
     *
     * @Route("commentaire/{id}/supprimer", name="commentaire.supprimer")
     * @param Request $request
     * @param Commentaire $com
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function supprimer(Request $request, Commentaire $com, EntityManagerInterface $em) : Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($com);
        $em->flush();
        return $this->redirectToRoute('crypto.index');
    }
    /**
     *
     * @Route("commentaire/{id}/modifier", name="commentaire.modifier")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function modifier(Request $request, EntityManagerInterface $em, $id)
    {
        $commentaire = $this->getDoctrine()->getRepository(Commentaire::class)->find($id);

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->get('description')->setData($commentaire->getDescription());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($commentaire);
            $em->flush();
            return $this->redirectToRoute('crypto.index');

        }
        return $this->render('commentaire/modifier.html.twig', [
            'com' =>$commentaire, 'form' => $form->createView()]);


    }
}
