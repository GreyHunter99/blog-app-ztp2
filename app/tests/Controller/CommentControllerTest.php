<?php
/**
 * Comment controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class CommentControllerTest.
 */
class CommentControllerTest extends WebTestCase
{
    /**
     * Test client.
     *
     * @var KernelBrowser
     */
    private $httpClient;

    /**
     * Set up tests.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test index route as non authorized user.
     */
    public function testIndexRouteNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser('user@example.com', [User::ROLE_USER]);
        $this->logIn($user);

        // when
        $this->httpClient->request('GET', '/comment');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test index route as authorized user.
     */
    public function testIndexRouteAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser('user@example.com', [User::ROLE_USER]);
        $this->logIn($user);

        // when
        $this->httpClient->request('GET', '/comment/'.$user->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test index route as admin.
     */
    public function testIndexRouteAdmin(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser('user@example.com', [User::ROLE_USER, User::ROLE_ADMIN]);
        $this->logIn($adminUser);

        // when
        $this->httpClient->request('GET', '/comment');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test create comment for anonymous user.
     */
    public function testCreateCommentAnonymousUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser('user@example.com', [User::ROLE_USER]);

        $post = $this->createPost($user);

        // when
        $this->httpClient->request('GET', '/comment/'.$post->getId().'/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test create comment for authorized user.
     */
    public function testCreateCommentAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser('user@example.com', [User::ROLE_USER]);
        $this->logIn($user);

        $post = $this->createPost($user);

        // when
        $crawler = $this->httpClient->request('GET', '/comment/'.$post->getId().'/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('Stwórz')->form();
        $form['comment[title]']->setValue('Test Comment');
        $form['comment[content]']->setValue('Test Comment Content');
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Stworzono pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test edit comment for non authorized user.
     */
    public function testEditCommentNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com', [User::ROLE_USER]);
        $user2 = $this->createUser('user2@example.com', [User::ROLE_USER]);
        $this->logIn($user1);

        $post = $this->createPost($user1);
        $expectedComment = $this->createComment($user2, $post);

        // when
        $this->httpClient->request('GET', '/comment/'.$expectedComment->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit comment for authorized user.
     */
    public function testEditCommentAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user1 = $this->createUser('user1@example.com', [User::ROLE_USER]);
        $user2 = $this->createUser('user2@example.com', [User::ROLE_USER]);
        $this->logIn($user2);

        $post = $this->createPost($user1);
        $expectedComment = $this->createComment($user2, $post);

        // when
        $crawler = $this->httpClient->request('GET', '/comment/'.$expectedComment->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zapisz')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test delete comment for non authorized user.
     */
    public function testDeleteCommentNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com', [User::ROLE_USER]);
        $user2 = $this->createUser('user2@example.com', [User::ROLE_USER]);
        $this->logIn($user1);

        $post = $this->createPost($user1);
        $expectedComment = $this->createComment($user2, $post);

        // when
        $this->httpClient->request('GET', '/comment/'.$expectedComment->getId().'/delete');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete comment for authorized user.
     */
    public function testDeleteCommentAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user1 = $this->createUser('user1@example.com', [User::ROLE_USER]);
        $user2 = $this->createUser('user2@example.com', [User::ROLE_USER]);
        $this->logIn($user2);

        $post = $this->createPost($user1);
        $expectedComment = $this->createComment($user2, $post);

        // when
        $crawler = $this->httpClient->request('GET', '/comment/'.$expectedComment->getId().'/delete');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Usuń')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Usunięto pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Simulate user log in.
     *
     * @param User $user User entity
     */
    private function logIn(User $user): void
    {
        $session = self::$container->get('session');

        $firewallName = 'main';
        $firewallContext = 'main';

        $token = new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->httpClient->getCookieJar()->set($cookie);
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
        $post->setPublished(true);

        $postRepository = self::$container->get(PostRepository::class);
        $postRepository->save($post);

        return $post;
    }

    /**
     * Create comment.
     *
     * @param User $user User entity
     * @param Post $post Post published
     *
     * @return Comment Comment entity
     */
    private function createComment(User $user, Post $post): Comment
    {
        $comment = new Comment();
        $comment->setTitle('Test Comment');
        $comment->setContent('Test Comment Content');
        $comment->setPost($post);
        $comment->setAuthor($user);

        $commentRepository = self::$container->get(CommentRepository::class);
        $commentRepository->save($comment);

        return $comment;
    }
}
