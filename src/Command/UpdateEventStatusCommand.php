<?php

namespace App\Command;

use App\Entity\Event;
use App\Enum\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:events:update-status',
    description: 'Met à jour automatiquement le statut des événements passés en COMPLETED'
)]
class UpdateEventStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour du statut des événements passés');

        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.eventDate < :now')
            ->andWhere('e.status = :published')
            ->setParameter('now', new \DateTimeImmutable('today'))
            ->setParameter('published', EventStatus::PUBLISHED)
            ->getQuery()
            ->getResult();

        $count = count($events);

        if (0 === $count) {
            $io->success('Aucun événement à mettre à jour.');

            return Command::SUCCESS;
        }

        $io->progressStart($count);

        foreach ($events as $event) {
            $event->setStatus(EventStatus::COMPLETED);
            $this->entityManager->persist($event);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('%d événement(s) mis à jour avec le statut COMPLETED.', $count));

        return Command::SUCCESS;
    }
}
