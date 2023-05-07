<?php

namespace App\DataFixtures;

use App\Entity\Publication;
use App\Entity\User;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {

        $faker = Faker\Factory::create('fr_FR');

        // Fixture de création de compte administrateur
        $admin = new User();

        $admin
            ->setEmail('a@a.a')
            ->setRegistrationDate($faker->dateTimeBetween('-1 year', 'now') )
            ->setPseudonym('Adam')
            ->setRoles(["ROLE_ADMIN"])
            ->setPassword(
                $this->encoder->hashPassword($admin, 'aaaaaaaaaA/1')
            )
        ;

        $manager->persist($admin);

        // Fixture de création de 20 comptes utilisateur

        for($i = 0; $i < 20; $i++){

            $user = new User();

            $user
                ->setEmail( $faker->email )
                ->setRegistrationDate( $faker->dateTimeBetween('-1 year', 'now') )
                ->setPseudonym( $faker->userName )
                ->setPassword(
                    $this->encoder->hashPassword($user, 'aaaaaaaaaA/1')
                )
            ;

        $manager->persist($user);
        }

        // Fixture de création de 100 publications
        for($i = 0; $i < 100; $i++){

            $publication = new publication();

            $publication
                ->setTitle( $faker->sentence(7) )
                ->setContent( $faker->paragraph(20) )
                ->setPublicationDate( $faker->dateTimeBetween('-1 year', 'now') )
                ->setAuthor( $admin )
            ;

            $manager->persist($publication);

            // Création entre 0 et 10 commentaires aléatoires par publication
            $rand = rand(0, 10);

            for($j = 0; $j < $rand; $j++){

                // Création d'un nouveau commentaire
                $newComment = new Comment();

                // Hydratation du commentaire
                $newComment
                    ->setPublication($publication)

                    // Date aléatoire
                    ->setPublicationDate($faker->dateTimeBetween( '-1 year' , 'now'))

                    // Auteur aléatoire parmis les comptes créés plus haut
                    ->setAuthor($faker->randomElement($user))
                    ->setContent($faker->paragraph(5))
                ;

                // Persistance du commentaire
                $manager->persist($newComment);

            }

        }

        $manager->flush();
    }

}
