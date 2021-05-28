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
    private $commentRepository;

    /**
     * Comment service.
     *
     * @var CommentService|object|null
     */
    private $commentService;

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
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        $this->commentRepository = $container->get(CommentRepository::class);
        $this->commentService = $container->get(CommentService::class);
        $this->postService = $container->get(PostService::class);
        $this->categoryService = $container->get(CategoryService::class);
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
        $user = $this->createUser('user@example.com');
        $expectedComment = $this->createComment('Test Comment', $this->createPost($user), $user);

        // when
        $this->commentService->save($expectedComment);
        $resultComment = $this->commentRepository->findOneById(
            $expectedComment->getId()
        );

        // then
        $this->assertEquals($expectedComment, $resultComment);
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
        $user = $this->createUser('user@example.com');
        $expectedComment = $this->createComment('Test Comment', $this->createPost($user), $user);
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
        $expectedResultSize = 0;

        // when
        $result = $this->commentService->createPaginatedList($page, null, null);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list admin index page.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListAdminIndex(): void
    {
        // given
        $page = 2;
        $expectedResultSize = 1;

        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');

        $post1 = $this->createPost($user1);
        $post2 = $this->createPost($user2);

        $comment = $this->createComment('Test Comment #1', $post1, $user1);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #2', $post1, $user2);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #3', $post2, $user1);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #4', $post2, $user2);
        $this->commentRepository->save($comment);

        // when
        $result = $this->commentService->createPaginatedList($page, null, null);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list by post.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListPost(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 2;

        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');

        $post1 = $this->createPost($user1);
        $post2 = $this->createPost($user2);

        $comment = $this->createComment('Test Comment #1', $post1, $user1);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #2', $post1, $user2);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #3', $post2, $user1);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #4', $post2, $user2);
        $this->commentRepository->save($comment);

        // when
        $result = $this->commentService->createPaginatedList($page, $post1, null);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list by comment author.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedListAuthor(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 2;

        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');

        $post1 = $this->createPost($user1);
        $post2 = $this->createPost($user2);

        $comment = $this->createComment('Test Comment #1', $post1, $user1);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #2', $post1, $user2);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #3', $post2, $user1);
        $this->commentRepository->save($comment);

        $comment = $this->createComment('Test Comment #4', $post2, $user2);
        $this->commentRepository->save($comment);

        // when
        $result = $this->commentService->createPaginatedList($page, null, $user1);

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
     * @param User $user User entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Post Post entity
     */
    private function createPost(User $user): Post
    {
        $category = new Category();
        $category->setName('Test Category');
        $this->categoryService->save($category);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Post Content');
        $post->setCategory($category);
        $post->setAuthor($user);
        $this->postService->save($post);

        return $post;
    }

    /**
     * Create comment.
     *
     * @param string $title Comment title
     * @param Post   $post  Post entity
     * @param User   $user  User entity
     *
     * @return Comment Comment entity
     */
    private function createComment(string $title, Post $post, User $user): Comment
    {
        $comment = new Comment();
        $comment->setTitle($title);
        $comment->setContent('Test Comment Content');
        $comment->setPost($post);
        $comment->setAuthor($user);

        return $comment;
    }
}
