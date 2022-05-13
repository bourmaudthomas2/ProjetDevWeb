<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserPromoteCommand extends Command
{
    protected static $defaultName = 'app:user:promote';
    private $om;

    public function __construct(EntityManagerInterface $om)
    {
        $this->om = $om;

        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setDescription("Ajoute un role à un utilisateur")
            ->addArgument('username', InputArgument::REQUIRED, 'Nom d\'utilisateur')
            ->addArgument('roles', InputArgument::REQUIRED, 'Les roles a ajouter')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $roles = $input->getArgument('roles');
        $userRepo = $this->om->getRepository(User::class);
        $user = $userRepo->findOneByUsername($username);
        if ($user) {
            $user->addRoles($roles);
            $this->om->flush();

            $io->success('Le role a été ajouté');
        } else {
            $io->error('Il n\'y a pas d\'utilisateur avec ce pseudo');
        }

        return 0;
    }
}
