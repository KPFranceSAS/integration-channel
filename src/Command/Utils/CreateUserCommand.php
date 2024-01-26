<?php

namespace App\Command\Utils;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:utils-create-user', 'Create an user')]
class CreateUserCommand extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly UserPasswordHasherInterface $passwordEncoder)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, 'email ')
            ->addArgument('password', InputArgument::REQUIRED, 'password ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setPassword($this->passwordEncoder->hashPassword($user, $password));
        $user->setEmail($username);
        $this->manager->persist($user);
        $this->manager->flush();
        return Command::SUCCESS;
    }

    private $manager;
}
