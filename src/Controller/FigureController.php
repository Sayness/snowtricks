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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use App\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FigureController extends AbstractController
{
    private string $mediaDirectory;

    public function __construct(ParameterBagInterface $params)
    {
        $this->mediaDirectory = $params->get('media_directory');
    }

    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedia(Media $media): self
    {
        if (!$this->media->contains($media)) {
            $this->media[] = $media;
            $media->setFigure($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): self
    {
        if ($this->media->removeElement($media) && $media->getFigure() === $this) {
            $media->setFigure(null);
        }

        return $this;
    }

    #[Route('/', name: 'figure_index')]
    public function index(FigureRepository $figureRepository): Response
    {
        $figures = $figureRepository->findAll();

        return $this->render('figure/index.html.twig', [
            'figures' => $figures,
        ]);
    }

    #[Route('/figure/new', name: 'figure_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
     $figure = new Figure();
    $form = $this->createForm(FigureType::class, $figure);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Récupère les fichiers envoyés depuis le champ 'media'
        $mediaFiles = $form->get('media')->getData();   
        // dd(count($mediaFiles));
        // Vérifie si $mediaFiles est un tableau de fichiers
        if (is_array($mediaFiles) && count($mediaFiles) > 0) {
            foreach ($mediaFiles as $file) {
                if ($file instanceof UploadedFile) {
                    // Copie temporaire et déplacement
                    $tempPath = $this->getParameter('media_directory') . '/temp_' . uniqid() . '.' . $file->guessExtension();
                    if (!copy($file->getRealPath(), $tempPath)) {
                        throw new \Exception("Erreur lors de la copie du fichier temporaire.");
                    }

                    $fileName = uniqid() . '.' . $file->guessExtension();
                    rename($tempPath, $this->getParameter('media_directory') . '/' . $fileName);

                    // Création de l'objet Media
                    $media = new Media();
                    $media->setUrl($fileName);
                    $media->setType(str_starts_with($file->getMimeType(), 'image') ? 'image' : 'video');
                    $media->setFigure($figure);

                    // Ajout du média à la figure
                    $figure->addMediaFile($media);
                    $entityManager->persist($media); // Persiste chaque média individuellement
                }
            }
        }

        $entityManager->persist($figure);
        $entityManager->flush();

        return $this->redirectToRoute('figure_show', ['id' => $figure->getId()]);
    }

    return $this->render('figure/new.html.twig', [
        'form' => $form->createView(),
    ]);
    }
    
    private function getMediaType(File $file): string
    {
        $mimeType = $file->getMimeType();
        if (str_contains($mimeType, 'image')) {
            return 'image';
        } elseif (str_contains($mimeType, 'video')) {
            return 'video';
        }
        return 'unknown';
    }

    #[Route('/figure/{id}', name: 'figure_show')]
    public function show(int $id, FigureRepository $figureRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
   // Utilisation de la méthode pour charger la figure et ses médias
   $figure = $figureRepository->findFigureWithMedia($id);
    
   if (!$figure) {
       throw $this->createNotFoundException('Figure non trouvée.');
   }

   $comment = new Comment();
   $comment->setFigure($figure);
   $comment->setCreatedAt(new \DateTime());

   $form = $this->createForm(CommentFormType::class, $comment);
   $form->handleRequest($request);

   if ($form->isSubmitted() && $form->isValid()) {
       if ($this->getUser()) {
           $comment->setAuthor($this->getUser()->getUsername());
       }

       $entityManager->persist($comment);
       $entityManager->flush();

       return $this->redirectToRoute('figure_show', ['id' => $figure->getId()]);
   }
    
        return $this->render('figure/show.html.twig', [
            'figure' => $figure,
            'mediaFiles' => $figure->getMediaFiles(), // Média récupérés dans la figure
            'form' => $form->createView(),
            'commentForm' => $form->createView(),
        ]);
    }
    
    
    #[Route('/figure/{id}/edit', name: 'figure_edit')]
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
}
