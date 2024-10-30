<?php


// src/Controller/SecurityController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $entityManager->persist($user);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $form = $this->createForm(LoginFormType::class);

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }
    
    
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }
}
