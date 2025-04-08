<?php

namespace App\Controller;

use App\Entity\Foodtruck;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FoodtruckController extends AbstractController
{
    #[Route('/api/foodtrucks', name: 'get_foodtrucks', methods: ['GET'])]
    public function getFoodtrucks(EntityManagerInterface $em): JsonResponse
    {
        $foodtrucks = $em->getRepository(Foodtruck::class)->findAll();
        
        $data = [];
        foreach ($foodtrucks as $foodtruck) {
            $data[] = [
                'id' => $foodtruck->getId(),
                'name' => $foodtruck->getName(),
                'description' => $foodtruck->getDescription(),
                'typeCuisine' => $foodtruck->getTypeCuisine(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/foodtrucks', name: 'create_foodtruck', methods: ['POST'])]
    public function createFoodtruck(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['description'], $data['type_cuisine'])) {
            return new JsonResponse(['message' => 'Les paramètres name, description et type_cuisine sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $name = $data['name'];
        $description = $data['description'];
        $typeCuisine = $data['type_cuisine'];

        $foodtruck = new Foodtruck();
        $foodtruck->setName($name);
        $foodtruck->setDescription($description);
        $foodtruck->setTypeCuisine($typeCuisine);

        $em->persist($foodtruck);
        $em->flush();

        return new JsonResponse(['message' => 'Foodtruck créé avec succès'], JsonResponse::HTTP_CREATED);
    }
}