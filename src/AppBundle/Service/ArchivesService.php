<?php
/**
 * Votix. The advanded and secure online voting platform.
 *
 * @author Philippe Lewin <philippe.lewin@gmail.com>
 * @author Club*Nix <club.nix@edu.esiee.fr>
 * @license MIT
 */
namespace AppBundle\Service;

use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Class ArchivesService
 * @package AppBundle\Service
 */
class ArchivesService
{
    private $archiveFile;

    /**
     * ArchivesService constructor.
     * @param $archiveFile
     */
    public function __construct($archiveFile)
    {
        $this->archiveFile = $archiveFile;
    }

    /**
     * @return array
     */
    public function getArchive()
    {
        $yaml = new YamlParser();

        $value = $yaml->parse(file_get_contents($this->archiveFile));

        return $value;
    }

}