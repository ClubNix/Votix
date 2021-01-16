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
 * Class VotixCsvCommand
 */
class ImportCsvCommand extends Command
{
    private $entityManager;

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
            ->setName('votix:importcsv')
            ->setDescription('Import users with CSV file')
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $filepath = $input->getArgument('filepath');

        // decoding CSV contents
        $data = $this->serializer->decode(file_get_contents($filepath), 'csv', [
            // prevents default key separator "." from messing with the csv headers
            'csv_key_separator' => '=========',
        ]);

        $ligne = 1;
        foreach ($data as $entry) {
            // skip empty lines
            if (empty($entry['Prénom.Apprenant'])) {
                continue;
            }

            $login = trim($entry['Coordonnée.Coordonnée']);
            $firstname = $entry['Prénom.Apprenant'];
            $lastname = $entry['Nom.Apprenant'];
            $promotion = trim($entry['Code.Groupe']);
            $email = trim($entry['Coordonnée.Coordonnée']);

            $voter = new Voter();
            $voter->setLogin($login);
            $voter->setFirstname($firstname);
            $voter->setLastname($lastname);
            $voter->setEmail($email);
            $voter->setPromotion($promotion);

            $this->entityManager->persist($voter);

            echo "Line $ligne: " . $voter . PHP_EOL;
            $ligne++;
        }

        $this->entityManager->flush();
    }
}
