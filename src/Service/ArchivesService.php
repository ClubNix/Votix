<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Class ArchivesService
 */
class ArchivesService
{
    private $archiveFile;

    /**
     * ArchivesService constructor.
     *
     * @param string $archiveFile
     */
    public function __construct(string $archiveFile)
    {
        $this->archiveFile = $archiveFile;
    }

    /**
     * @return array
     */
    public function getArchive(): array
    {
        $yaml = new YamlParser();

        return $yaml->parse(file_get_contents($this->archiveFile));
    }

}