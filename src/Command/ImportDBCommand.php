<?php

namespace App\Command;

use App\Repository\DoctrineRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportDBCommand extends Command
{
    /**
     * @var DoctrineRepository
     */
    private DoctrineRepository $repository;
    /**
     * @var DoctrineRepository
     */
    private DoctrineRepository $db;

    public function __construct(DoctrineRepository $sqlite3, DoctrineRepository $db)
    {
        parent::__construct();
        $this->repository = $db;
        $this->db = $sqlite3;
    }

    protected static $defaultName = 'app:importdb';

    protected function configure()
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = 1000;
        $skip = 0;
        do {
            $count = 0;
            $results = $this->repository->exportImages($skip, $limit);
            foreach ($results as $result) {
                $this->db->insertImage($result);
                $count++;
            }
            $skip = $skip + $count;
            $io->note('Finished ' . $skip);
        } while ($count === $limit);
        $io->success('Images imported');
        $skip = 0;
        do {
            $count = 0;
            $results = $this->repository->exportArchives($skip, $limit);
            foreach ($results as $result) {
                $this->db->insertArchive($result);
                $count++;
            }
            $skip = $skip + $count;
            $io->note('Finished ' . $skip);
        } while ($count === $limit);
        $io->success('Archives imported');
        return 0;
    }
}
