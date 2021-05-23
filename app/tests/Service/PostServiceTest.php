<?php
/**
 * PostService tests.
 */

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\PostService;
use App\Service\CategoryService;
use App\Service\UserService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PostServiceTest.
 */
class PostServiceTest extends KernelTestCase
{
    /**
     * Post repository.
     *
     * @var PostRepository|object|null
     */
    private ?PostRepository $postRepository;

    /**
     * Post service.
     *
     * @var PostService|object|null
     */
    private ?PostService $postService;

    /**
     * Category service.
     *
     * @var CategoryService|object|null
     */
    private ?CategoryService $categoryService;

    /**
     * Test category.
     *
     * @var Category
     */
    private $testCategory;

    /**
     * Test user.
     *
     * @var User
     */
    private $testUser;

    /**
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        $this->postRepository = $container->get(PostRepository::class);
        $this->postService = $container->get(PostService::class);
        $this->categoryService = $container->get(CategoryService::class);
        $this->testUser = $this->createUser([User::ROLE_USER]);
        $this->testCategory = new Category();
        $this->testCategory->setName('Test Category');
        $this->categoryService->save($this->testCategory);
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
        $expectedPost = new Post();
        $expectedPost->setTitle('Test Post');
        $expectedPost->setContent('Test Post Content');
        $expectedPost->setCategory($this->testCategory);
        $expectedPost->setAuthor($this->testUser);

        // when
        $this->postService->save($expectedPost);
        $expectedPost = $this->postRepository->findOneById(
            $expectedPost->getId()
        );

        // then
        $this->assertEquals($expectedPost, $expectedPost);
    }

    /**
     * Test delete.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(): void
    {
        // given
        $expectedPost = new Post();
        $expectedPost->setTitle('Test Post');
        $expectedPost->setContent('Test Post Content');
        $expectedPost->setCategory($this->testCategory);
        $expectedPost->setAuthor($this->testUser);
        $this->postRepository->save($expectedPost);
        $expectedId = $expectedPost->getId();

        // when
        $this->postService->delete($expectedPost);
        $result = $this->postRepository->findOneById($expectedId);

        // then
        $this->assertNull($result);
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

            $post = new Post();
            $post->setTitle('Test Post #'.$counter);
            $post->setContent('Test Post Content #'.$counter);
            $post->setCategory($this->testCategory);
            $post->setAuthor($this->testUser);
            $post->setPublished(true);

            $this->postRepository->save($post);

            ++$counter;
        }

        // when
        $result = $this->postService->createPaginatedList($page, 'main', null, ['category_id' => $this->testCategory->getId(), 'search' => 'Test']);
        $result_admin = $this->postService->createPaginatedList($page, 'main_admin', null);
        $result_profile = $this->postService->createPaginatedList($page, 'profile', $this->testUser);
        $result_author = $this->postService->createPaginatedList($page, 'profile_author', $this->testUser);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
        $this->assertEquals($expectedResultSize, $result_admin->count());
        $this->assertEquals($expectedResultSize, $result_profile->count());
        $this->assertEquals($expectedResultSize, $result_author->count());
    }

    // other tests for paginated list

    /**
     * Create user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $userService = self::$container->get(UserService::class);
        $userRepository = self::$container->get(UserRepository::class);

        $user = $userRepository->findOneBy(array('email' => 'user@example.com'));
        if (!$user) {
            $passwordEncoder = self::$container->get('security.password_encoder');
            $user = new User();
            $user->setEmail('user@example.com');
            $user->setRoles($roles);
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    'p@55w0rd'
                )
            );

            $userService->save($user);
        }

        return $user;
    }
}