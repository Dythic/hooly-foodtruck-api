<?php

namespace App\Service;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailReminderService
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    
    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer) 
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }
    
    public function sendReminders(): void
    {
        $tomorrow = new \DateTime('tomorrow');
        $tomorrow->setTime(0, 0, 0);
        
        $reservations = $this->entityManager->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.date_reservation = :tomorrow')
            ->setParameter('tomorrow', $tomorrow->format('Y-m-d'))
            ->getQuery()
            ->getResult();
            
        foreach ($reservations as $reservation) {
            $foodtruck = $reservation->getFoodtruck();
            $campus = $reservation->getCampus();
            
            $foodtruckEmail = 'contact@' . strtolower(str_replace(' ', '', $foodtruck->getName())) . '.com';
            
            $email = (new Email())
                ->from('reservations@hooly.com')
                ->to($foodtruckEmail)
                ->subject('Rappel de votre réservation pour demain')
                ->html(
                    '<p>Bonjour ' . $foodtruck->getName() . ',</p>' .
                    '<p>Nous vous rappelons que vous avez une réservation pour demain ' . $tomorrow->format('d/m/Y') . 
                    ' sur le campus de ' . $campus->getName() . '.</p>' .
                    '<p>Horaires d\'accès : 11h00 - 14h00</p>' .
                    '<p>Merci et à demain !</p>' .
                    '<p>L\'équipe Hooly</p>'
                );
                
            $this->mailer->send($email);
        }
    }
}
