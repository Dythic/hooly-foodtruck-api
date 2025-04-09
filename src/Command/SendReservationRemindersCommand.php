<?php

namespace App\Command;

use App\Service\EmailReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-reservation-reminders',
    description: 'Envoie des rappels aux foodtrucks pour les réservations du lendemain',
)]
class SendReservationRemindersCommand extends Command
{
    private EmailReminderService $reminderService;
    
    public function __construct(EmailReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Envoi des rappels pour les réservations de demain...');
        
        try {
            $this->reminderService->sendReminders();
            $output->writeln('Rappels envoyés avec succès.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Erreur lors de l\'envoi des rappels : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
