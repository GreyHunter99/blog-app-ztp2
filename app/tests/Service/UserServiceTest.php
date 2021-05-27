<?php
/**
 * UserService tests.
 */

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserServiceTest.
 */
class UserServiceTest extends KernelTestCase
{
    /**
     * User service.
     *
     * @var UserService|object|null
     */
    private $userService;

    /**
     * User repository.
     *
     * @var UserRepository|object|null
     */
    private $userRepository;

    /**
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        $this->userRepository = $container->get(UserRepository::class);
        $this->userService = $container->get(UserService::class);
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
        $expectedUser = $this->createUser('user@example.com' ,[User::ROLE_USER]);

        // when
        $this->userService->save($expectedUser);
        $expectedUser = $this->userRepository->findOneById(
            $expectedUser->getId()
        );

        // then
        $this->assertEquals($expectedUser, $expectedUser);
    }

    /**
     * Test pagination empty list.
     */
    public function testCreatePaginatedListEmptyList(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 0;

        // when
        $result = $this->userService->createPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedList(): void
    {
        // given
        $page = 1;
        $dataSetSize = 3;
        $expectedResultSize = 3;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $user = $this->createUser('testUser'.$counter.'@example.com' ,[User::ROLE_USER]);
            $this->userRepository->save($user);

            ++$counter;
        }

        // when
        $result = $this->userService->createPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test number of admins.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testNumberOfAdmins(): void
    {
        // given
        $dataSetSize = 2;
        $expectedResultSize = 2;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $user = $this->createUser('user'.$counter.'@example.com' ,[User::ROLE_USER]);
            $this->userRepository->save($user);

            ++$counter;
        }

        $counter = 0;
        while ($counter < $dataSetSize) {
            $user = $this->createUser('admin'.$counter.'@example.com' ,[User::ROLE_USER, User::ROLE_ADMIN]);
            $this->userRepository->save($user);

            ++$counter;
        }


        // when
        $result = $this->userService->numberOfAdmins();

        // then
        $this->assertEquals($expectedResultSize, $result);
    }

    /**
     * Create user.
     *
     * @param string $email User email
     * @param array  $roles User roles
     *
     * @return User User entity
     */
    private function createUser(string $email, array $roles): User
    {
        $passwordEncoder = self::$container->get('security.password_encoder');
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                'p@55w0rd'
            )
        );

        return $user;
    }
}