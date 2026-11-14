<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Create an admin or staff account')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address')
            ->addArgument('name', InputArgument::REQUIRED, 'Full name')
            ->addArgument('role', InputArgument::OPTIONAL, 'ROLE_ADMIN or ROLE_STAFF', User::ROLE_STAFF);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $role = $input->getArgument('role');

        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
            $io->error('Role must be ROLE_ADMIN or ROLE_STAFF.');

            return Command::FAILURE;
        }

        $password = (string) $io->askHidden('Password (at least 6 characters)');
        if (strlen($password) < 6) {
            $io->error('The password must have at least 6 characters.');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($input->getArgument('email'))
            ->setName($input->getArgument('name'))
            ->setRole($role);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Account created: %s (%s)', $user->getEmail(), $role));

        return Command::SUCCESS;
    }
}