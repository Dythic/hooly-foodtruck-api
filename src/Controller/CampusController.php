<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CampusController extends AbstractController
{
    #[Route('/api/campus/{id}/available-slots', name: 'get_available_slots', methods: ['GET'])]
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

        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($weekdays as $day) {
            if (!isset($availableSlots[$day])) {
                $availableSlots[$day] = 0;
            }
        }

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
