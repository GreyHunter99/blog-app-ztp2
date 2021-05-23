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
    private ?UserService $userService;

    /**
     * User repository.
     *
     * @var UserRepository|object|null
     */
    private ?UserRepository $userRepository;

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
        $expectedUser = $this->userRepository->findOneBy(array('email' => 'test@example.com'));
        if (!$expectedUser) {
            $passwordEncoder = self::$container->get('security.password_encoder');
            $expectedUser = new User();
            $expectedUser->setEmail('test@example.com');
            $expectedUser->setRoles([User::ROLE_USER]);
            $expectedUser->setPassword(
                $passwordEncoder->encodePassword(
                    $expectedUser,
                    'p@55w0rd'
                )
            );

            // when
            $this->userService->save($expectedUser);
        }

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
        $dataSetSize = 3;
        $expectedResultSize = 3;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $this->createUser('test'.$counter.'@example.com' ,[User::ROLE_USER]);

            ++$counter;
        }

        // when
        $result = $this->userService->createPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    // other tests for paginated list

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
        $user = $this->userRepository->findOneBy(array('email' => $email));
        if (!$user) {
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

            $this->userRepository->save($user);
        }

        return $user;
    }
}