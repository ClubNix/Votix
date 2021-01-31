<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use App\Entity\Voter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Class VoterImportCommand
 */
class VoterImportCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DecoderInterface
     */
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, DecoderInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:voter:import')
            ->setDescription('Import voters with CSV file')
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'CSV file to import'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filepath = $input->getArgument('filepath');

        // decoding CSV contents
        $data = $this->serializer->decode(file_get_contents($filepath), 'csv', [
            // prevents default key separator "." from messing with the csv headers
            'csv_key_separator' => '=========',
        ]);

        $counter = 1;
        foreach ($data as $entry) {
            // skip empty lines
            if (empty($entry['Prénom.Apprenant'])) {
                continue;
            }

            $login = strstr(trim($entry['Coordonnée.Coordonnée']), '@', true);
            $firstname = trim($entry['Prénom.Apprenant']);
            $lastname = trim($entry['Nom.Apprenant']);
            $promotion = trim($entry['Code.Groupe']);
            $email = trim($entry['Coordonnée.Coordonnée']);

            $voter = new Voter();
            $voter->setLogin($login);
            $voter->setFirstname($firstname);
            $voter->setLastname($lastname);
            $voter->setEmail($email);
            $voter->setPromotion($promotion);

            $this->entityManager->persist($voter);

            $output->writeln("Line $counter: " . $voter);
            $counter++;
        }

        $this->entityManager->flush();

        $output->writeln('Done');

        return Command::SUCCESS;
    }
}
