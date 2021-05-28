<?php
/**
 * User data fixtures.
 */

namespace App\DataFixtures;

use App\Entity\UserData;
use Doctrine\Persistence\ObjectManager;

/**
 * Class UserDataFixtures.
 */
class UserDataFixtures extends AbstractBaseFixtures
{
    /**
     * Load data.
     *
     * @param ObjectManager $manager Persistence object manager
     */
    public function loadData(ObjectManager $manager): void
    {
        $this->createMany(10, 'userdata', function ($i) {
            $userData = new UserData();
            $userData->setName($this->faker->firstName);
            $userData->setDescription($this->faker->text);
            $userData->setUser($this->getReference('users_'.$i));

            return $userData;
        });

        $this->createMany(3, 'userdata-admin', function ($i) {
            $userData = new UserData();
            $userData->setName($this->faker->firstName);
            $userData->setDescription($this->faker->text);
            $userData->setUser($this->getReference('admins_'.$i));

            return $userData;
        });

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return array Array of dependencies
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
