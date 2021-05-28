<?php
/**
 * PhotoService tests.
 */

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Photo;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PhotoRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\PhotoService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class PhotoServiceTest.
 */
class PhotoServiceTest extends KernelTestCase
{
    /**
     * Photo service.
     *
     * @var PhotoService|object|null
     */
    private $photoService;

    /**
     * Photo repository.
     *
     * @var PhotoRepository|object|null
     */
    private $photoRepository;

    /**
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        $this->photoRepository = $container->get(PhotoRepository::class);
        $this->photoService = $container->get(PhotoService::class);
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
        $user = $this->createUser();

        $expectedPhoto = new Photo();
        $expectedPhoto->setFilename(__DIR__.'/../fixtures/test.jpg');
        $expectedPhoto->setPost($this->createPost($user));

        // when
        $this->photoService->save($expectedPhoto);
        $resultPhoto = $this->photoRepository->findOneById(
            $expectedPhoto->getId()
        );

        // then
        $this->assertEquals($expectedPhoto, $resultPhoto);
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
        $user = $this->createUser();

        $expectedPhoto = new Photo();
        $expectedPhoto->setFilename(__DIR__.'/../fixtures/test.jpg');
        $expectedPhoto->setPost($this->createPost($user));
        $this->photoRepository->save($expectedPhoto);
        $expectedId = $expectedPhoto->getId();

        // when
        $this->photoService->delete($expectedPhoto);
        $result = $this->photoRepository->findOneById($expectedId);

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
        $expectedResultSize = 0;
        $user = $this->createUser();
        $post = $this->createPost($user);

        // when
        $result = $this->photoService->createPaginatedList($page, $post);

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
        $dataSetSize = 2;
        $expectedResultSize = 1;

        $user = $this->createUser();
        $post1 = $this->createPost($user);
        $post2 = $this->createPost($user);

        $counter = 0;
        while ($counter < $dataSetSize) {
            $photo = new Photo();
            $photo->setFilename(__DIR__.'/../fixtures/test'.$counter.'.jpg');
            $photo->setPost($post1);
            $this->photoRepository->save($photo);

            ++$counter;
        }
        $photo = new Photo();
        $photo->setFilename(__DIR__.'/../fixtures/test'.$counter.'.jpg');
        $photo->setPost($post2);
        $this->photoRepository->save($photo);

        // when
        $result = $this->photoService->createPaginatedList($page, $post2);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Create user.
     *
     * @return User User entity
     */
    private function createUser(): User
    {
        $passwordEncoder = self::$container->get('security.password_encoder');
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles([User::ROLE_USER]);
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                'p@55w0rd'
            )
        );
        $userRepository = self::$container->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }

    /**
     * Create post.
     *
     * @param User $user User entity
     *
     * @return Post Post entity
     */
    private function createPost(User $user): Post
    {
        $category = new Category();
        $category->setName('Test Category');
        $categoryRepository = self::$container->get(CategoryRepository::class);
        $categoryRepository->save($category);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Post Content');
        $post->setCategory($category);
        $post->setAuthor($user);

        $postRepository = self::$container->get(PostRepository::class);
        $postRepository->save($post);

        return $post;
    }
}