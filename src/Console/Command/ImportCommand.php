<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace App\Console\Command;

use App\Entity\Voter;
use Doctrine\ORM\EntityManager;
use Sabre\Xml\LibXMLException;
use Sabre\Xml\Reader as XmlReader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class VotixImportCommand
 */
class ImportCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('votix:import')
            ->setDescription('Import users with DSML file')
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'DSML file to import'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws LibXMLException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filepath = $input->getArgument('filepath');
        if($filepath[0] == '.') {
            return;
        }

        $finder = new Finder();
        $finder->files()->in($filepath);

        $found = [];

        /** @var EntityManager $em */
        $em = $this->get('doctrine')->getManager();

        foreach ($finder as $file) {
            $reader = new XmlReader();
            $reader->xml(file_get_contents($file->getRealpath()));
            $result = $reader->parse();

            $entries = $result['value'][0]['value'];

            $header = $entries[0];

            $promo = $header['attributes']['dn'];

            $first_part = explode(',', $promo)[0];

            $promo = explode('=', $first_part)[1];

            unset($entries[0]);

            foreach($entries as $entry) {
                if($entry['name'] == '{urn:oasis:names:tc:DSML:2:0:core}searchResultDone') {
                    // ignore
                    continue;
                }

                $attributes = [];

                foreach($entry['value'] as $attribute) {
                    $attributes[$attribute['attributes']['name']] = $attribute['value'][0]['value'];
                }

                $allowed = ['annuairePresent', 'mail', 'uid', 'givenName', 'mailEDU', 'sn', 'presentGMail'];

                $attributes = array_intersect_key($attributes, array_flip($allowed));

                $login = $attributes['uid'];
                if(array_key_exists($login, $found)) {
                    $em->flush();

                    /** @var Voter $oldvoter */
                    $oldvoter = $em->getRepository('App:Voter')->findOneBy(['login' => $login]);


                    if(strcmp($promo, $found[$login]) > 0) {
                        $output->writeln('Updating : Skipping ' . $login . ' as in ' . $found[$login] . ' found again in ' . $promo);
                        $oldvoter->setPromotion($promo);
                        $em->persist($oldvoter);
                    } else {
                        $output->writeln('Keeping : Skipping ' . $login . ' as in ' . $found[$login] . ' found again in ' . $promo);
                    }
                    continue;
                }

                $voter = new Voter();

                $voter->setLogin($attributes['uid']);
                $voter->setFirstname($attributes['givenName']);
                $voter->setLastname($attributes['sn']);
                $voter->setPromotion($promo);
                $voter->setEmail($attributes['mail']);
                $voter->setBallot(null);

                $em->persist($voter);

                $found[$login] = $promo;
            }
        }

        $em->flush();

    }
}