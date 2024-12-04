<?php

namespace App\DataFixtures;

use App\Entity\Figure;
use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Entity\Comment;

class AppFixtures extends Fixture
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function load(ObjectManager $manager): void
    {
        $publicPath = $this->kernel->getProjectDir() . '/public/uploads/media/';

        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0777, true);
        }

        // Liste des figures 
        $figures = [
            [
                'nom' => 'Big Air',
                'description' => 'Une figure réalisée après un saut important sur un grand tremplin, permettant au rider d’effectuer des rotations complexes ou des grabs spectaculaires.',
                'categorie' => 'big_air',
                'image' => 'ollie.jpg',
                'video' => 'ollie.mp4',
            ],
            [
                'nom' => 'Half-pipe',
                'description' => 'Une figure exécutée dans une structure en forme de demi-cylindre où le rider effectue des sauts, des rotations ou des flips sur les bords.',
                'categorie' => 'half_pipe',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Quarter-pipe',
                'description' => 'Variante du half-pipe, cette figure se pratique sur une structure en quart de cylindre, souvent utilisée pour des rotations ou des grabs en sortie.',
                'categorie' => 'quarter_pipe',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Barre de slide',
                'description' => 'Glisser sur une barre métallique en utilisant la planche, perpendiculairement ou dans l’axe, avec parfois des rotations pour l’entrée ou la sortie.',
                'categorie' => 'barre_slide',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Step-up',
                'description' => 'Une figure où le rider saute d’un niveau inférieur à un niveau supérieur, souvent combinée avec des grabs ou rotations.',
                'categorie' => 'step_up',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Waterslide',
                'description' => 'Une figure où le rider glisse sur une surface d’eau avec la planche, nécessitant précision et équilibre.',
                'categorie' => 'waterslide',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Box',
                'description' => 'Glisser sur une structure plate et large, souvent utilisée pour débuter ou perfectionner les slides.',
                'categorie' => 'box',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Wall',
                'description' => 'Une figure où le rider utilise un mur ou une surface verticale pour effectuer des tricks ou des rebonds.',
                'categorie' => 'wall',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Kicker',
                'description' => 'Sauter à partir d’un tremplin incliné, souvent combiné avec des grabs ou des flips pour maximiser le style.',
                'categorie' => 'kicker',
                'image' => 'ollie.jpg',
            ],
            [
                'nom' => 'Road Gap',
                'description' => 'Une figure consistant à sauter par-dessus une route ou un obstacle similaire, nécessitant beaucoup de vitesse et de précision.',
                'categorie' => 'road_gap',
                'image' => 'ollie.jpg',
            ],
        ];
        

        foreach ($figures as $index => $data) {
            // Création de l'entité Figure
            $figure = new Figure();
            $figure->setNom($data['nom']);
            $figure->setDescription($data['description']);
            $figure->setCategories($data['categorie']);
            $figure->setCreatedAt(new \DateTimeImmutable());
            $figure->generateSlug();
        
            $mediaImage = new Media();
            $mediaImage->setUrl($data['image']); // Enregistre nom du fichier
            $mediaImage->setAlt($data['nom']); // Texte alt
            $mediaImage->setType('image'); // Définit le type
            $figure->addMediaFile($mediaImage); // Associe l'image à la figure
        
            $sourceImage = $this->kernel->getProjectDir() . '/public/default.jpg';
            $destinationImage = $publicPath . $data['image'];
            if (!file_exists($destinationImage)) {
                copy($sourceImage, $destinationImage);
            }
        
            $manager->persist($mediaImage);
        
            if (!empty($data['video'])) {
                $mediaVideo = new Media();
                $mediaVideo->setUrl($data['video']); // Enregistre nom du fichier
                $mediaVideo->setAlt($data['nom'] . ' video'); // Texte alt
                $mediaVideo->setType('video'); // Définit le type 
                $figure->addMediaFile($mediaVideo); // Associe la vidéo à la figure
        
                $sourceVideo = $this->kernel->getProjectDir() . '/public/default.mp4';
                $destinationVideo = $publicPath . $data['video'];
                if (!file_exists($destinationVideo)) {
                    copy($sourceVideo, $destinationVideo);
                }
        
                $manager->persist($mediaVideo);
            }
        
            $manager->persist($figure);
        
            // Ajouter 10 commentaires à la première figure
            if ($index === 0) {
                for ($i = 1; $i <= 10; $i++) {
                    $comment = new Comment();
                    $comment->setContent("Commentaire fictif numéro $i");
                    $comment->setCreatedAt(new \DateTime(sprintf('-%d days', $i))); // Random dates 
                    $comment->setAuthor("Auteur $i");
                    $comment->setFigure($figure);
        
                    $manager->persist($comment);
                }
            }
        }
        
        $manager->flush();
    }
}
