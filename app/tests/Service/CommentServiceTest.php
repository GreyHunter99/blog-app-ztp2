<?php
/**
 * CommentService tests.
 */

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Service\CategoryService;
use App\Service\CommentService;
use App\Service\PostService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CommentServiceTest.
 */
class CommentServiceTest extends KernelTestCase
{
    /**
     * Comment repository.
     *
     * @var CommentRepository|object|null
     */
    private ?CommentRepository $commentRepository;

    /**
     * Comment service.
     *
     * @var CommentService|object|null
     */
    private ?CommentService $commentService;

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
     * Test post.
     *
     * @var Post
     */
    private $testPost;

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
        $this->commentRepository = $container->get(CommentRepository::class);
        $this->commentService = $container->get(CommentService::class);
        $this->postService = $container->get(postService::class);
        $this->categoryService = $container->get(CategoryService::class);
        $this->testUser = $this->createUser([User::ROLE_USER]);
        $this->testPost = $this->createPost();
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
        $expectedComment = new Comment();
        $expectedComment->setTitle('Test Comment');
        $expectedComment->setContent('Test Comment Content');
        $expectedComment->setPost($this->testPost);
        $expectedComment->setAuthor($this->testUser);

        // when
        $this->commentService->save($expectedComment);
        $expectedComment = $this->commentRepository->findOneById(
            $expectedComment->getId()
        );

        // then
        $this->assertEquals($expectedComment, $expectedComment);
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
        $expectedComment = new Comment();
        $expectedComment->setTitle('Test Comment');
        $expectedComment->setContent('Test Comment Content');
        $expectedComment->setPost($this->testPost);
        $expectedComment->setAuthor($this->testUser);
        $this->commentRepository->save($expectedComment);
        $expectedId = $expectedComment->getId();

        // when
        $this->commentService->delete($expectedComment);
        $result = $this->commentRepository->findOneById($expectedId);

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

            $comment = new Comment();
            $comment->setTitle('Test Comment #'.$counter);
            $comment->setContent('Test Comment Content #'.$counter);
            $comment->setPost($this->testPost);
            $comment->setAuthor($this->testUser);

            $this->commentRepository->save($comment);

            ++$counter;
        }

        // when
        $result = $this->commentService->createPaginatedList($page, null, null);
        $result_post = $this->commentService->createPaginatedList($page, $this->testPost, null);
        $result_user = $this->commentService->createPaginatedList($page, null, $this->testUser);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
        $this->assertEquals($expectedResultSize, $result_post->count());
        $this->assertEquals($expectedResultSize, $result_user->count());
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

            $userRepository->save($user);
        }

        return $user;
    }

    /**
     * Create post.
     *
     * @return Post Post entity
     */
    private function createPost(): Post
    {
        $category = new Category();
        $category->setName('Test Category');
        $this->categoryService->save($category);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Post Content');
        $post->setCategory($category);
        $post->setAuthor($this->testUser);
        $this->postService->save($post);

        return $post;
    }
}