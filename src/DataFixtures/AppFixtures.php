<?php

namespace App\DataFixtures;

use App\Entity\Resource;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {

        $admin = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin');
        $admin
            ->setUsername('admin')
            ->setPhone('+380800111111')
            ->setPassword($hashedPassword)
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'test');
        $user
            ->setUsername('test')
            ->setPhone('+380631234569')
            ->setPassword($hashedPassword)
            ->setRoles(['ROLE_USER']);
        $manager->persist($user);

        $this->generateResources($manager);

        $manager->flush();
    }

    public function generateResources(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $resource = new Resource();
            $resource->setName('Ресурс #'.$i);

            $manager->persist($resource);
        }
    }
}
