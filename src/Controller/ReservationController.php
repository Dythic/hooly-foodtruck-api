<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Foodtruck;
use App\Entity\Campus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Réservations')]
class ReservationController extends AbstractController
{
    #[Route('/api/reservations', name: 'get_reservations_by_campus_and_date', methods: ['GET'])]
    #[OA\Get(
        path: '/api/reservations',
        summary: 'Obtenir les réservations par campus et date',
        parameters: [
            new OA\Parameter(name: 'campus_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des réservations'),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 404, description: 'Aucune réservation trouvée')
        ]
    )]
    public function getReservationsByCampusAndDate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $campusId = $request->query->get('campus_id');
        $date = $request->query->get('date');
        
        if (!$campusId || !$date) {
            return new JsonResponse(['message' => 'Le campus et la date sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dateObject = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObject) {
            return new JsonResponse(['message' => 'La date doit être au format YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $campus = $em->getRepository(Campus::class)->find($campusId);
        if (!$campus) {
            return new JsonResponse(['message' => 'Campus non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $from = new \DateTime($dateObject->format("Y-m-d") . " 00:00:00");
        $to = new \DateTime($dateObject->format("Y-m-d") . " 23:59:59");

        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from(Reservation::class, 'r')
            ->where('r.campus = :campus')
            ->andWhere('r.date_reservation BETWEEN :from AND :to')
            ->setParameter('campus', $campus)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $reservations = $qb->getQuery()->getResult();

        if (empty($reservations)) {
            return new JsonResponse(['message' => 'Aucune réservation trouvée pour ce campus et cette date.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $result = [];
        foreach ($reservations as $reservation) {
            $result[] = [
                'id' => $reservation->getId(),
                'foodtruck' => $reservation->getFoodtruck()->getName(),
                'campus' => $reservation->getCampus()->getName(),
                'date_reservation' => $reservation->getDateReservation()->format('Y-m-d'),
            ];
        }

        return new JsonResponse($result, JsonResponse::HTTP_OK);
    }

    #[Route('/api/reservations', name: 'create_reservation', methods: ['POST'])]
    #[OA\Post(
        path: '/api/reservations',
        summary: 'Créer une nouvelle réservation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'foodtruck_id', type: 'integer'),
                new OA\Property(property: 'campus_id', type: 'integer'),
                new OA\Property(property: 'date_reservation', type: 'string', format: 'date'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Réservation créée'),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 404, description: 'Campus ou Foodtruck non trouvé')
        ]
    )]
    public function createReservation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $foodtruckId = $data['foodtruck_id'] ?? null;
        $campusId = $data['campus_id'] ?? null;
        $date = $data['date_reservation'] ?? null;
        
        if (!$foodtruckId || !$campusId || !$date) {
            return new JsonResponse(['message' => 'Les paramètres foodtruck, campus et date sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dateObject = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObject) {
            return new JsonResponse(['message' => 'La date doit être au format YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        
        if ($dateObject < $tomorrow) {
            return new JsonResponse([
                'message' => 'La réservation doit être effectuée au moins 1 jour à l\'avance.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $foodtruck = $em->getRepository(Foodtruck::class)->find($foodtruckId);
        $campus = $em->getRepository(Campus::class)->find($campusId);

        if (!$foodtruck || !$campus) {
            return new JsonResponse(['message' => 'Foodtruck ou Campus non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $weekStart = clone $dateObject;
        $weekEnd = clone $dateObject;
        $weekStart->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd->modify('sunday this week')->setTime(23, 59, 59);

        $weeklyReservations = $em->createQueryBuilder()
            ->select('COUNT(r)')
            ->from(Reservation::class, 'r')
            ->where('r.foodtruck = :foodtruck')
            ->andWhere('r.campus = :campus')
            ->andWhere('r.date_reservation BETWEEN :start AND :end')
            ->setParameter('foodtruck', $foodtruck)
            ->setParameter('campus', $campus)
            ->setParameter('start', $weekStart)
            ->setParameter('end', $weekEnd)
            ->getQuery()
            ->getSingleScalarResult();
        
        if ($weeklyReservations >= 1) {
            return new JsonResponse([
                'message' => 'Ce foodtruck a déjà réservé un emplacement sur ce campus cette semaine.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $otherCampusReservation = $em->createQueryBuilder()
            ->select('r')
            ->from(Reservation::class, 'r')
            ->where('r.foodtruck = :foodtruck')
            ->andWhere('r.campus != :campus')
            ->andWhere('r.date_reservation = :date')
            ->setParameter('foodtruck', $foodtruck)
            ->setParameter('campus', $campus)
            ->setParameter('date', $dateObject)
            ->getQuery()
            ->getResult();
        
        if (!empty($otherCampusReservation)) {
            return new JsonResponse([
                'message' => 'Ce foodtruck est déjà réservé sur un autre campus pour cette date.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingReservation = $em->createQueryBuilder()
            ->select('r')
            ->from(Reservation::class, 'r')
            ->where('r.foodtruck = :foodtruck')
            ->andWhere('r.campus = :campus')
            ->andWhere('r.date_reservation = :date')
            ->setParameter('foodtruck', $foodtruck)
            ->setParameter('campus', $campus)
            ->setParameter('date', $dateObject)
            ->getQuery()
            ->getResult();

        if (!empty($existingReservation)) {
            return new JsonResponse([
                'message' => 'Ce foodtruck a déjà réservé un emplacement pour ce campus cette journée.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dayOfWeek = strtolower($dateObject->format('l'));
        $availableSlots = $campus->getAvailableSlots();
        
        if (!isset($availableSlots[$dayOfWeek]) || $availableSlots[$dayOfWeek] <= 0) {
            return new JsonResponse([
                'message' => 'Aucun créneau disponible pour ce jour sur ce campus.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingReservations = $em->createQueryBuilder()
            ->select('COUNT(r)')
            ->from(Reservation::class, 'r')
            ->where('r.campus = :campus')
            ->andWhere('r.date_reservation = :date')
            ->setParameter('campus', $campus)
            ->setParameter('date', $dateObject)
            ->getQuery()
            ->getSingleScalarResult();

        if ($existingReservations >= $availableSlots[$dayOfWeek]) {
            return new JsonResponse([
                'message' => 'Tous les créneaux sont déjà réservés pour ce jour sur ce campus.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $reservation = new Reservation();
        $reservation->setFoodtruck($foodtruck);
        $reservation->setCampus($campus);
        $reservation->setDateReservation($dateObject);

        $em->persist($reservation);
        $em->flush();

        return new JsonResponse(['message' => 'Réservation créée avec succès'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/reservations/{id}', name: 'cancel_reservation', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/reservations/{id}',
        summary: 'Annuler une réservation',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Réservation annulée'),
            new OA\Response(response: 404, description: 'Réservation non trouvée')
        ]
    )]
    public function cancelReservation(int $id, EntityManagerInterface $em): JsonResponse
    {
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($reservation);
        $em->flush();

        return new JsonResponse(['message' => 'Réservation annulée avec succès'], JsonResponse::HTTP_OK);
    }
}