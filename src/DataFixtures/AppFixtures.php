<?php

namespace App\DataFixtures;

use App\Entity\Publication;
use App\Entity\User;
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
                $this->encoder->hashPassword($admin, 'aaaaaaaaa/A1')
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
                    $this->encoder->hashPassword($user, 'aaaaaaaaa/A1')
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

        }

        $manager->flush();
    }
}
