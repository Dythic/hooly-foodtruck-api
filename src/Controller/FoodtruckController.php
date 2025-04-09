<?php

namespace App\Controller;

use App\Entity\Foodtruck;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Foodtrucks')]
class FoodtruckController extends AbstractController
{
    #[Route('/api/foodtrucks', name: 'get_foodtrucks', methods: ['GET'])]
    #[OA\Get(
        path: '/api/foodtrucks',
        summary: 'Obtenir la liste des foodtrucks',
        responses: [
            new OA\Response(response: 200, description: 'Liste des foodtrucks')
        ]
    )]
    public function getFoodtrucks(EntityManagerInterface $em): JsonResponse
    {
        $foodtrucks = $em->getRepository(Foodtruck::class)->findAll();
        
        $data = [];
        foreach ($foodtrucks as $foodtruck) {
            $data[] = [
                'id' => $foodtruck->getId(),
                'name' => $foodtruck->getName(),
                'email' => $foodtruck->getEmail(),
                'description' => $foodtruck->getDescription(),
                'typeCuisine' => $foodtruck->getTypeCuisine(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/foodtrucks', name: 'create_foodtruck', methods: ['POST'])]
    #[OA\Post(
        path: '/api/foodtrucks',
        summary: 'Créer un nouveau foodtruck',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'type_cuisine', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Foodtruck créé'),
            new OA\Response(response: 400, description: 'Requête invalide')
        ]
    )]
    public function createFoodtruck(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['description'], $data['type_cuisine'])) {
            return new JsonResponse(['message' => 'Les paramètres name, description et type_cuisine sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $name = $data['name'];
        $email = $data['email'];
        $description = $data['description'];
        $typeCuisine = $data['type_cuisine'];

        $foodtruck = new Foodtruck();
        $foodtruck->setName($name);
        $foodtruck->setEmail($email);
        $foodtruck->setDescription($description);
        $foodtruck->setTypeCuisine($typeCuisine);

        $em->persist($foodtruck);
        $em->flush();

        return new JsonResponse(['message' => 'Foodtruck créé avec succès'], JsonResponse::HTTP_CREATED);
    }
}