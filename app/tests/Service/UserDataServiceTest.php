<?php
/**
 * UserDataService tests.
 */

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserData;
use App\Repository\UserDataRepository;
use App\Repository\UserRepository;
use App\Service\UserDataService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserDataServiceTest.
 */
class UserDataServiceTest extends KernelTestCase
{
    /**
     * User data service.
     *
     * @var UserDataService|object|null
     */
    private $userDataService;

    /**
     * User data repository.
     *
     * @var UserDataRepository|object|null
     */
    private $userDataRepository;

    /**
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        $this->userDataRepository = $container->get(UserDataRepository::class);
        $this->userDataService = $container->get(UserDataService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSave(): void
    {
        // given
        $passwordEncoder = self::$container->get('security.password_encoder');
        $expectedUser = new User();
        $expectedUser->setEmail('user@example.com');
        $expectedUser->setRoles([User::ROLE_USER]);
        $expectedUser->setPassword(
            $passwordEncoder->encodePassword(
                $expectedUser,
                'p@55w0rd'
            )
        );

        $expectedUserData = new UserData();
        $expectedUserData->setName('Test User');
        $expectedUserData->setDescription('Test User Description');

        $expectedUser->setUserData($expectedUserData);

        // when
        $this->userDataService->save($expectedUserData);

        $userRepository = self::$container->get(UserRepository::class);
        $userRepository->save($expectedUser);

        $resultUserData = $this->userDataRepository->findOneById($expectedUserData->getId());

        // then
        $this->assertEquals($expectedUserData, $resultUserData);
    }
}
