<?php

namespace App\DataFixtures;

use App\Entity\UserEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new UserEntity();
        $user->setEmail('admin@sendit.me');
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setRole('ROLE_SUPER_ADMIN');
        $password = $this->encoder->hashPassword($user, 'pass_1234');
        $user->setPassword($password);
        $user->setCreatedDate(new \DateTime());
        $manager->persist($user);
        $manager->flush();

        for ($count = 1; $count <= 15; ++$count) {
            $user = new UserEntity();
            $user->setEmail("user_{$count}@sendit.me");
            $user->setFirstName("Sendit {$count}");
            $user->setLastName("User {$count}");
            $user->setRole('ROLE_MEMBER');
            $password = $this->encoder->hashPassword($user, 'pass_1234');
            $user->setPassword($password);
            $user->setCreatedDate(new \DateTime());
            $manager->persist($user);
            $manager->flush();
        }
    }

    public static function getGroups(): array
    {
        return ['users'];
    }
}
