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

class ReservationController extends AbstractController
{
    #[Route('/api/reservations', name: 'get_reservations_by_campus_and_date', methods: ['GET'])]
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
    public function createReservation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $foodtruckId = $data['foodtruck_id'];
        $campusId = $data['campus_id'];
        $date = $data['date_reservation'];
        
        if (!$foodtruckId || !$campusId || !$date) {
            return new JsonResponse(['message' => 'Les paramètres foodtruck, campus et date sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dateObject = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObject) {
            return new JsonResponse(['message' => 'La date doit être au format YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $foodtruck = $em->getRepository(Foodtruck::class)->find($foodtruckId);
        $campus = $em->getRepository(Campus::class)->find($campusId);

        if (!$foodtruck || !$campus) {
            return new JsonResponse(['message' => 'Foodtruck ou Campus non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $from = new \DateTime($dateObject->format("Y-m-d") . " 00:00:00");
        $to = new \DateTime($dateObject->format("Y-m-d") . " 23:59:59");

        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from(Reservation::class, 'r')
            ->where('r.foodtruck = :foodtruck')
            ->andWhere('r.campus = :campus')
            ->andWhere('r.date_reservation BETWEEN :from AND :to')
            ->setParameter('foodtruck', $foodtruck)
            ->setParameter('campus', $campus)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $reservations = $qb->getQuery()->getResult();

        if (!empty($reservations)) {
            return new JsonResponse(['message' => 'Ce foodtruck a déjà réservé un emplacement pour ce campus cette journée.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $reservation = new Reservation();
        $reservation->setFoodtruck($foodtruck);
        $reservation->setCampus($campus);
        $reservation->setDateReservation(new \DateTime($data['date_reservation']));

        $em->persist($reservation);
        $em->flush();

        return new JsonResponse(['message' => 'Réservation créée avec succès'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/reservations/{id}', name: 'cancel_reservation', methods: ['DELETE'])]
    public function cancelReservation(int $id, EntityManagerInterface $em): JsonResponse
    {
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new JsonResponse(['message' => 'Réservation non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $campus = $reservation->getCampus();
        $dateReservation = $reservation->getDateReservation();

        $availableSlots = $campus->getAvailableSlots();
        $dateFormatted = $dateReservation->format('Y-m-d');

        if (isset($availableSlots[$dateFormatted])) {
            $availableSlots[$dateFormatted]++;
        } else {
            $availableSlots[$dateFormatted] = 1;
        }

        $campus->setAvailableSlots($availableSlots);
        
        $em->remove($reservation);
        $em->flush();

        return new JsonResponse(['message' => 'Réservation annulée et emplacement libéré'], JsonResponse::HTTP_OK);
    }
}
