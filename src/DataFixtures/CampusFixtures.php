<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $paris = new Campus();
        $paris->setName('Paris');
        $paris->setAvailableSlots([
            'monday' => 7,
            'tuesday' => 7,
            'wednesday' => 7,
            'thursday' => 7,
            'friday' => 6,
            'saturday' => 0,
            'sunday' => 0
        ]);
        $manager->persist($paris);
        
        $lyon = new Campus();
        $lyon->setName('Lyon');
        $lyon->setAvailableSlots([
            'monday' => 4,
            'tuesday' => 5,
            'wednesday' => 5,
            'thursday' => 5,
            'friday' => 5,
            'saturday' => 0,
            'sunday' => 0
        ]);
        $manager->persist($lyon);
        
        $manager->flush();
    }
}