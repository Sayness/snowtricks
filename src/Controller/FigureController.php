<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Entity\Comment;
use App\Entity\Media;
use App\Form\FigureType;
use App\Form\CommentFormType;
use App\Repository\FigureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FigureController extends AbstractController
{
    private string $mediaDirectory;

    public function __construct(ParameterBagInterface $params)
    {
        $this->mediaDirectory = $params->get('media_directory');
    }

    /**
     * Affiche la liste paginée des figures.
     */
    #[Route('/', name: 'figure_index')]
    public function index(FigureRepository $figureRepository, Request $request): Response
    {
        $limit = 4;
        $page = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $limit;

        $figures = $figureRepository->findBy([], [], $limit, $offset);
        $totalFigures = $figureRepository->count([]);
        $hasMoreFigures = ($offset + $limit) < $totalFigures;

        return $this->render('figure/index.html.twig', [
            'figures' => $figures,
            'hasMoreFigures' => $hasMoreFigures,
            'page' => $page,
        ]);
    }

    /**
     * Crée une nouvelle figure.
     */
    #[Route('/figure/new', name: 'figure_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $figure = new Figure();
        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le slug avant de persister
            $figure->generateSlug();
    
            // Gérer les médias ajoutés
            $mediaFiles = $form->get('media')->getData();
    
            if (is_array($mediaFiles) && count($mediaFiles) > 0) {
                foreach ($mediaFiles as $file) {
                    if ($file instanceof UploadedFile) {
                        $fileName = uniqid() . '.' . $file->guessExtension();
                        $filePath = $this->mediaDirectory . '/' . $fileName;
                        copy($file->getRealPath(), $filePath);
    
                        $media = new Media();
                        $media->setUrl($fileName);
                        $media->setType(str_starts_with($file->getMimeType(), 'image') ? 'image' : 'video');
                        $media->setFigure($figure);
    
                        $figure->addMediaFile($media);
                        $entityManager->persist($media);
                    }
                }
            }
    
            $entityManager->persist($figure);
            $entityManager->flush();
    
            return $this->redirectToRoute('figure_show', ['slug' => $figure->getSlug()]);
        }
    
        return $this->render('figure/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    

    private function handleCommentForm(Figure $figure, Request $request, EntityManagerInterface $entityManager)
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Il faut être connecter pour mettre un commentaire');
        }
    
        $comment = new Comment();
        $comment->setFigure($figure);
        $comment->setCreatedAt(new \DateTime());
    
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser()) {
                $comment->setAuthor($this->getUser()->getUserIdentifier());
            }
    
            $entityManager->persist($comment);
            $entityManager->flush();
    
            return $this->redirectToRoute('figure_show', ['slug' => $figure->getSlug()]);
        }
    
        return $form;
    }
    


    // Get commentaires triés
    private function getPaginatedComments(Figure $figure, Request $request, EntityManagerInterface $entityManager): array
    {
        $limit = 3;
        $page = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $limit;
    
        $comments = $entityManager
            ->getRepository(Comment::class)
            ->findBy(
                ['figure' => $figure],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );
    
        $totalComments = $entityManager
            ->getRepository(Comment::class)
            ->count(['figure' => $figure]);
    
        return [
            'comments' => $comments,
            'totalComments' => $totalComments,
            'currentPage' => $page,
        ];
    }

    /**
     * Affiche les détails d'une figure avec ses médias et permet d'ajouter des commentaires.
     */
    #[Route('/figure/{slug}', name: 'figure_show')]
    public function show(
        string $slug,
        FigureRepository $figureRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $figure = $figureRepository->findOneBy(['slug' => $slug]);
        if (!$figure) {
            throw $this->createNotFoundException('Figure non trouvée.');
        }
    
        // Gestion des commentaires paginés
        $commentsData = $this->getPaginatedComments($figure, $request, $entityManager);
    
        // Gestion du formulaire de commentaire
        $commentForm = $this->handleCommentForm($figure, $request, $entityManager);
        if ($commentForm instanceof Response) {
            return $commentForm; // Redirection après soumission
        }
      
        return $this->render('figure/show.html.twig', [
            'figure' => $figure,
            'comments' => $commentsData['comments'],
            'totalComments' => $commentsData['totalComments'],
            'currentPage' => $commentsData['currentPage'],
            'commentForm' => $commentForm->createView(),
        ]);
    }
    
    
    /**
     * Supprime une figure avec vérification du token CSRF.
     */
    #[Route('/figure/{slug}/delete', name: 'figure_delete', methods: ['POST'])]
    public function delete(Request $request, string $slug, FigureRepository $figureRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $figure = $figureRepository->findOneBy(['slug' => $slug]);
        
        if ($this->isCsrfTokenValid('delete-figure-' . $figure->getId(), $request->request->get('_token'))) {
            $entityManager->remove($figure);
            $entityManager->flush();

            $this->addFlash('success', 'Figure supprimée avec succès.');
        }

        return $this->redirectToRoute('figure_index');
    }

    /**
     * Modifie une figure existante, gère les médias et enregistre les modifications.
     */
    #[Route('/figure/{slug}/edit', name: 'figure_edit')]
    public function edit(string $slug, FigureRepository $figureRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $figure = $figureRepository->findOneBy(['slug' => $slug]);
        if (!$figure) {
            throw $this->createNotFoundException('Figure non trouvée.');
        }

        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Check si figure existe
            $existingFigure = $entityManager->getRepository(Figure::class)->findOneBy(['nom' => $figure->getNom()]);

            if ($existingFigure && $existingFigure->getId() !== $figure->getId()) {
                $this->addFlash('error', 'Ce nom de figure est déjà utilisé. Veuillez en choisir un autre.');
                return $this->redirectToRoute('figure_edit', ['slug' => $figure->getSlug()]);
            }

            $figure->generateSlug();
            $figure->setUpdatedAt();
            $entityManager->persist($figure);
            $entityManager->flush();
            $mediaDirectory = $this->getParameter('media_directory');

            // Gérer les médias supprimés
            $deletedMediaIds = $request->request->get('deleted_media');
            if ($deletedMediaIds) {
                foreach (explode(',', $deletedMediaIds) as $mediaId) {
                    $media = $entityManager->getRepository(Media::class)->find($mediaId);
                    if ($media) {
                        $filePath = $mediaDirectory . '/' . $media->getUrl();
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        $figure->removeMediaFile($media);
                        $entityManager->remove($media);
                    }
                }
            }

            
            // Gestion des remplacements de médias
            $mediaReplacements = $request->files->get('media_replacements', []);
            foreach ($mediaReplacements as $mediaId => $uploadedFile) {
                $media = $entityManager->getRepository(Media::class)->find($mediaId);
            
                if ($media && $uploadedFile instanceof UploadedFile && $uploadedFile->isReadable()) {
                    try {
                        // Supprimer l'ancien fichier
                        $oldFilePath = $mediaDirectory . '/' . $media->getUrl();
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
            
                        // Générer un nom de fichier et copier immédiatement
                        $newFileName = uniqid() . '.' . $uploadedFile->guessExtension();
                        $filePath = $mediaDirectory . '/' . $newFileName;
            
                        if (!copy($uploadedFile->getRealPath(), $filePath)) {
                            throw new \Exception("Impossible de copier le fichier temporaire.");
                        }
            
                        // Mettre à jour l'entité Media 
                        $media->setUrl($newFileName);
                        $media->setType(str_starts_with($uploadedFile->getMimeType(), 'image/') ? 'image' : 'video');
            
                        $entityManager->persist($media);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du remplacement du média.');
                    }
                } else {
                    dd('Le fichier uploadé n\'est pas lisible ou est inaccessible.', $uploadedFile);
                }
            }
        
        

            // Gérer les nouveaux médias
            $mediaFiles = $form->get('media')->getData();

            if ($mediaFiles) {
                foreach ($mediaFiles as $file) {
                    if ($file instanceof UploadedFile && $file->isReadable()) {
                        try {
                            // Générer un nom de fichier unique
                            $fileName = uniqid() . '.' . $file->guessExtension();
                            $filePath = $mediaDirectory . '/' . $fileName;
            
                            // Copier le fichier temporaire pour éviter sa suppression prématurée
                            if (!copy($file->getRealPath(), $filePath)) {
                                throw new \Exception("Impossible de copier le fichier temporaire.");
                            }
            
                            // Créer une nouvelle entité Media
                            $media = new Media();
                            $media->setUrl($fileName);
                            $media->setType(
                                str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video'
                            );
                            $media->setFigure($figure);
                            $entityManager->persist($media);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du nouveau média.');
                        }
                    } else {
                        dd('Le fichier uploadé n\'est pas lisible ou est inaccessible.', $file);
                    }
                }
            }

            try {
                $entityManager->flush();
                $this->addFlash('success', 'La figure a été mise à jour avec succès.');
                return $this->redirectToRoute('figure_show', ['slug' => $figure->getSlug()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la sauvegarde des modifications.');
            }
        }

        return $this->render('figure/edit.html.twig', [
            'figure' => $figure,
            'form' => $form->createView(),
        ]);
    }
}
