<?php

namespace App\DataFixtures;

use App\Entity\Annonces;
use App\Entity\Images;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AnnoncesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $img = $faker->image('public/uploads');
        $nomImg =  basename($img);

        for ($nbAnnonces = 1; $nbAnnonces <= 50; $nbAnnonces++) {
            $user = $this->getReference('user_' . $faker->numberBetween(1, 30));
            $categorie = $this->getReference('categorie_' . $faker->numberBetween(1, 4));

            $annonce = new Annonces();
            $annonce->setUsers($user);
            $annonce->setCategories($categorie);
            $annonce->setTitle($faker->realText(25));
            $annonce->setContent($faker->realText(200));
            $annonce->setActive($faker->numberBetween(0, 1));

            // Upload et génère les images
            // Problème avec faker et lorempixel est down 
            for ($image = 1; $image < 4; $image++) {
                $imageAnnonce = new Images();
                $imageAnnonce->setName($nomImg);
                $annonce->addImage($imageAnnonce);
            }
            $manager->persist($annonce);
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CategoriesFixtures::class,
            UsersFixtures::class
        ];
    }
}