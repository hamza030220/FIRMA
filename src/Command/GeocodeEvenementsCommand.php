<?php

namespace App\Command;

use App\Repository\Event\EvenementRepository;
use App\Service\Event\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:event:geocode',
    description: 'Backfill latitude/longitude on events that have an address but no coordinates yet.',
)]
final class GeocodeEvenementsCommand extends Command
{
    public function __construct(
        private readonly EvenementRepository $repo,
        private readonly EntityManagerInterface $em,
        private readonly GeocodingService $geocoder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $events = $this->repo->createQueryBuilder('e')
            ->andWhere('e.latitude IS NULL OR e.longitude IS NULL')
            ->andWhere('(e.adresse IS NOT NULL AND e.adresse != \'\') OR (e.lieu IS NOT NULL AND e.lieu != \'\')')
            ->getQuery()
            ->getResult();

        $total = is_array($events) ? count($events) : 0;
        if ($total === 0) {
            $io->success('Aucun événement à géocoder.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('%d événement(s) à géocoder. Respect du rate-limit Nominatim (1 req/s).', $total));
        $io->progressStart($total);

        $ok = 0;
        $ko = 0;
        foreach ($events as $evt) {
            $candidates = $this->geocoder->buildCandidates($evt->getAdresse(), $evt->getLieu());
            if ($candidates === []) {
                $ko++;
                $io->progressAdvance();
                continue;
            }
            $coords = $this->geocoder->geocodeBest($candidates);
            if ($coords !== null) {
                $evt->setLatitude((string) $coords['lat']);
                $evt->setLongitude((string) $coords['lng']);
                $ok++;
            } else {
                $ko++;
                $io->writeln(sprintf("\n  <comment>! Aucun match pour #%d - %s</comment>", $evt->getIdEvenement(), $evt->getTitre() ?? ''));
            }
            $io->progressAdvance();
            // Politeness delay (cache hits skip the API but stay fast)
            usleep(500_000);
        }

        $this->em->flush();
        $io->progressFinish();
        $io->success(sprintf('Géocodage terminé : %d réussis, %d échoués sur %d.', $ok, $ko, $total));

        return Command::SUCCESS;
    }
}
