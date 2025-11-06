<?php

namespace App\Command;

use App\Entity\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminUserCommand extends Command
{
    protected static $defaultName = 'app:create-admin-user';
    protected static $defaultDescription = 'Creates an admin user for testing purposes';

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if admin user already exists
        $existingUser = $this->entityManager->getRepository(UserEntity::class)->findOneBy(['email' => 'admin@sendit.me']);
        
        if ($existingUser) {
            $io->warning('Admin user already exists with email: admin@sendit.me');
            $io->info('Email: admin@sendit.me');
            $io->info('Password: pass_1234');
            $io->info('Role: ROLE_SUPER_ADMIN');
            return Command::SUCCESS;
        }

        // Create new admin user
        $user = new UserEntity();
        $user->setEmail('admin@sendit.me');
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setRole('ROLE_SUPER_ADMIN');
        $user->setEmailVerified(true);
        $user->setActive(true);
        $user->setCreatedDate(new \DateTime());
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'pass_1234');
        $user->setPassword($hashedPassword);

        // Persist and flush
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');
        $io->info('Email: admin@sendit.me');
        $io->info('Password: pass_1234');
        $io->info('Role: ROLE_SUPER_ADMIN');

        return Command::SUCCESS;
    }
}
