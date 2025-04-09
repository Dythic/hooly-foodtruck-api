<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Campus')]
class CampusController extends AbstractController
{
    #[Route('/api/campus', name: 'get_campus_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/campus',
        summary: 'Obtenir la liste des campus',
        responses: [
            new OA\Response(response: 200, description: 'Liste des campus')
        ]
    )]
    public function getCampusList(EntityManagerInterface $em): JsonResponse
    {
        $campuses = $em->getRepository(Campus::class)->findAll();
        
        $data = [];
        foreach ($campuses as $campus) {
            $data[] = [
                'id' => $campus->getId(),
                'name' => $campus->getName(),
                'available_slots' => $campus->getAvailableSlots(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/campus/{id}/available-slots', name: 'get_available_slots', methods: ['GET'])]
    #[OA\Get(
        path: '/api/campus/{id}/available-slots',
        summary: 'Obtenir les créneaux disponibles pour un campus à une date spécifique',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Créneaux disponibles'),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 404, description: 'Campus non trouvé')
        ]
    )]
    public function getAvailableSlots(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $date = $request->query->get('date');
        
        if (!$date) {
            return new JsonResponse(['message' => 'La date est requise.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dateObject = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObject) {
            return new JsonResponse(['message' => 'La date doit être au format YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dayOfWeek = strtolower($dateObject->format('l'));

        $campus = $em->getRepository(Campus::class)->find($id);
        if (!$campus) {
            return new JsonResponse(['message' => 'Campus non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $availableSlots = $campus->getAvailableSlots();

        if (!isset($availableSlots[$dayOfWeek]) || $availableSlots[$dayOfWeek] <= 0) {
            return new JsonResponse([
                'message' => 'Aucun créneau disponible pour ce jour.'
            ], JsonResponse::HTTP_OK);
        }

        $totalSlotsPerDay = $availableSlots[$dayOfWeek];

        $reservations = $em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.campus = :campus')
            ->andWhere('r.date_reservation = :date')
            ->setParameter('campus', $campus)
            ->setParameter('date', $dateObject->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $reservedSlots = count($reservations);

        if ($reservedSlots >= $totalSlotsPerDay) {
            return new JsonResponse([
                'campus' => $campus->getName(),
                'date' => $date,
                'message' => 'Tous les créneaux sont réservés pour ce jour.'
            ], JsonResponse::HTTP_OK);
        }

        $remainingSlots = $totalSlotsPerDay - $reservedSlots;

        return $this->json([
            'campus' => $campus->getName(),
            'date' => $date,
            'remaining_slots' => $remainingSlots
        ]);
    }
}