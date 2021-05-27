<?php
/**
 * PostService tests.
 */

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\CategoryService;
use App\Service\PostService;
use App\Service\TagService;
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
    private $postRepository;

    /**
     * Post service.
     *
     * @var PostService|object|null
     */
    private $postService;

    /**
     * Category service.
     *
     * @var CategoryService|object|null
     */
    private $categoryService;

    /**
     * Tag service.
     *
     * @var TagService|object|null
     */
    private $tagService;

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
        $this->tagService = $container->get(TagService::class);
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
        $expectedPost = $this->createPost('Test Post', 'Test category', 'Test Tag', true, $this->createUser('user@example.com'));

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
        $expectedPost = $this->createPost('Test Post', 'Test category', 'Test Tag', true, $this->createUser('user@example.com'));
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
        $expectedResultSize = 0;

        // when
        $result = $this->postService->createPaginatedList($page, 'main', null);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list main page.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListMain(): void
    {
        // given
        $page = 1;
        $dataSetSize = 2;
        $expectedResultSize = 2;

        $user = $this->createUser('user@example.com');

        $counter = 0;
        while ($counter < $dataSetSize) {
            $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag', true, $user);
            $this->postRepository->save($post);
            ++$counter;
        }
        $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag', false, $user);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'main', null);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list main page as admin.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListMainAdmin(): void
    {
        // given
        $page = 1;
        $dataSetSize = 2;
        $expectedResultSize = 3;

        $user = $this->createUser('user@example.com');

        $counter = 0;
        while ($counter < $dataSetSize) {
            $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag', true, $user);
            $this->postRepository->save($post);
            ++$counter;
        }
        $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag', false, $user);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'main_admin', null);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list user profile.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListProfile(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 1;

        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');

        $post = $this->createPost('Test Post #1', 'Test category', 'Test Tag', true, $user1);
        $this->postRepository->save($post);

        $post = $this->createPost('Test Post #2', 'Test category', 'Test Tag', false, $user1);
        $this->postRepository->save($post);

        $post = $this->createPost('Test Post #3', 'Test category', 'Test Tag', true, $user2);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'profile', $user1);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list user profile as profile owner.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListProfileAuthor(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 2;

        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');

        $post = $this->createPost('Test Post #1', 'Test category', 'Test Tag', true, $user1);
        $this->postRepository->save($post);

        $post = $this->createPost('Test Post #2', 'Test category', 'Test Tag', false, $user1);
        $this->postRepository->save($post);

        $post = $this->createPost('Test Post #3', 'Test category', 'Test Tag', true, $user2);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'profile_author', $user1);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list with category filter.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListCategory(): void
    {
        // given
        $page = 1;
        $dataSetSize = 2;
        $expectedResultSize = 1;

        $user = $this->createUser('user@example.com');

        $counter = 0;
        while ($counter < $dataSetSize) {
            $post = $this->createPost('Test Post #'.$counter, 'Test category 1', 'Test Tag', true, $user);
            $this->postRepository->save($post);
            ++$counter;
        }
        $post = $this->createPost('Test Post #'.$counter, 'Test category 2', 'Test Tag', true, $user);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'main', null, ['category_id' => $post->getCategory()->getId()]);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list with tag filter.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListTag(): void
    {
        // given
        $page = 1;
        $dataSetSize = 2;
        $expectedResultSize = 1;

        $user = $this->createUser('user@example.com');

        $counter = 0;
        while ($counter < $dataSetSize) {
            $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag 1', true, $user);
            $this->postRepository->save($post);
            ++$counter;
        }
        $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag 2', true, $user);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'main', null, ['tag_id' => $post->getTags()[0]->getId()]);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list with search filter.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListSearch(): void
    {
        // given
        $page = 1;
        $dataSetSize = 2;
        $expectedResultSize = 2;

        $user = $this->createUser('user@example.com');

        $counter = 0;
        while ($counter < $dataSetSize) {
            $post = $this->createPost('Test Post #'.$counter.' decoy', 'Test category', 'Test Tag', true, $user);
            $this->postRepository->save($post);
            ++$counter;
        }
        $post = $this->createPost('Test Post #'.$counter, 'Test category', 'Test Tag', true, $user);
        $this->postRepository->save($post);

        // when
        $result = $this->postService->createPaginatedList($page, 'main', null, ['search' => 'decoy']);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Create user.
     *
     * @param string $email User email
     *
     * @return User User entity
     */
    private function createUser(string $email): User
    {
        $passwordEncoder = self::$container->get('security.password_encoder');
        $user = new User();
        $user->setEmail($email);
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
     * @param string $title        Post title
     * @param string $categoryName Post category name
     * @param string $tagName      Post tag name
     * @param bool   $published    Post published
     * @param User   $user         User entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Post Post entity
     */
    private function createPost(string $title, string $categoryName, string $tagName, bool $published, User $user): Post
    {
        $category = new Category();
        $category->setName($categoryName);
        $this->categoryService->save($category);

        $tag = new Tag();
        $tag->setName($tagName);
        $this->tagService->save($tag);

        $post = new Post();
        $post->setTitle($title);
        $post->setContent('Test Post Content');
        $post->setCategory($category);
        $post->addTag($tag);
        $post->setAuthor($user);
        $post->setPublished($published);

        return $post;
    }
}