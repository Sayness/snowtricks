<?php

// src/Controller/FigureController.php

namespace App\Controller;

use App\Entity\Figure;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\FigureType;
use App\Form\CommentFormType;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use App\Repository\FigureRepository; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class FigureController extends AbstractController
{
    #[Route('/', name: 'figure_index')]
    public function index(FigureRepository $figureRepository): Response
    {
        $figures = $figureRepository->findAll();

        return $this->render('figure/index.html.twig', [
            'figures' => $figures,
        ]);
    }

    #[Route('/figure/new', name: 'figure_new')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] 
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $figure = new Figure();
        $form = $this->createForm(FigureType::class, $figure);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($figure);
            $entityManager->flush();

            return $this->redirectToRoute('figure_index');
        }

        return $this->render('figure/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/figure/{id}', name: 'figure_show')]
    public function show(Figure $figure, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $comment->setFigure($figure);
        $comment->setCreatedAt(new \DateTime());
    
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->getUser()) {
                $comment->setAuthor($this->getUser()->getEmail()); 
            } else {
                $this->addFlash('error', 'Vous devez être connecté pour commenter.');
                return $this->redirectToRoute('figure_show', ['id' => $figure->getId()]);
            }
    
            $entityManager->persist($comment);
            $entityManager->flush();
    
            return $this->redirectToRoute('figure_show', ['id' => $figure->getId()]);
        }
    
        return $this->render('figure/show.html.twig', [
            'figure' => $figure,
            'commentForm' => $form->createView(),
        ]);
    }
    

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/figure/{id}/edit', name: 'figure_edit')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] 
    public function edit(Request $request, Figure $figure, FigureRepository $figureRepository): Response
    {
        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $figureRepository->save($figure, true);
    
            return $this->redirectToRoute('figure_show', ['id' => $figure->getId()]);
        }
    
        return $this->render('figure/edit.html.twig', [
            'figure' => $figure,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        return $this->render('security/login.html.twig', []);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout() {}
}
