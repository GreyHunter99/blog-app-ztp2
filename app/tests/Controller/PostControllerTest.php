<?php
/**
 * Post controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class PostControllerTest.
 */
class PostControllerTest extends WebTestCase
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
     * Test index route search.
     */
    public function testIndexRouteSearch(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request('GET', '/');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->filter('form')->form();
        $form['search']->setValue('query');
        $this->httpClient->submit($form);

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
        $adminUser = $this->createUser([User::ROLE_USER, User::ROLE_ADMIN], false);
        $this->logIn($adminUser);

        // when
        $this->httpClient->request('GET', '/');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show post.
     */
    public function testShowPost(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser([User::ROLE_USER], false);
        $expectedPost = $this->createPost($user, true);

        // when
        $this->httpClient->request('GET', '/'.$expectedPost->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals($expectedStatusCode, $result->getStatusCode());
    }

    /**
     * Test show post not published.
     */
    public function testShowPostNotPublished(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser([User::ROLE_USER], false);
        $expectedPost = $this->createPost($user, false);

        // when
        $this->httpClient->request('GET', '/'.$expectedPost->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals($expectedStatusCode, $result->getStatusCode());
    }

    /**
     * Test show post blocked user.
     */
    public function testShowPostBlockedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser([User::ROLE_USER], true);
        $expectedPost = $this->createPost($user, true);

        // when
        $this->httpClient->request('GET', '/'.$expectedPost->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals($expectedStatusCode, $result->getStatusCode());
    }

    /**
     * Test create post for anonymous user.
     */
    public function testCreatePostAnonymousUser(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', '/category/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test create post for authorized user.
     */
    public function testCreatePostAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser([User::ROLE_USER], false);
        $this->logIn($user);

        $category = new Category();
        $category->setName('Test Category');
        $categoryRepository = self::$container->get(CategoryRepository::class);
        $categoryRepository->save($category);

        // when
        $crawler = $this->httpClient->request('GET', '/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('Stwórz')->form();
        $form['post[title]']->setValue('Test Post');
        $form['post[content]']->setValue('Test Post Content');
        $form['post[category]']->select($category->getId());
        $form['post[published]']->tick();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Stworzono pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test edit post for anonymous user.
     */
    public function testEditPostAnonymousUser(): void
    {
        // given
        $expectedStatusCode = 302;

        $user = $this->createUser([User::ROLE_USER], false);
        $expectedPost = $this->createPost($user, true);

        // when
        $this->httpClient->request('GET', '/'.$expectedPost->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit post for authorized user.
     */
    public function testEditPostAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser([User::ROLE_USER], false);
        $this->logIn($user);

        $expectedPost = $this->createPost($user, true);

        // when
        $crawler = $this->httpClient->request('GET', '/'.$expectedPost->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zapisz')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test delete post for anonymous user.
     */
    public function testDeletePostAnonymousUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser([User::ROLE_USER], false);
        $expectedPost = $this->createPost($user, true);

        // when
        $this->httpClient->request('GET', '/'.$expectedPost->getId().'/delete');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete post for authorized user.
     */
    public function testDeletePostAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser([User::ROLE_USER], false);
        $this->logIn($user);

        $expectedPost = $this->createPost($user, true);

        // when
        $crawler = $this->httpClient->request('GET', '/'.$expectedPost->getId().'/delete');
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
     * @param array $roles   User roles
     * @param bool  $blocked User blocked
     *
     * @return User User entity
     */
    private function createUser(array $roles, bool $blocked): User
    {
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
        $user->setBlocked($blocked);

        $userRepository = self::$container->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }

    /**
     * Create post.
     *
     * @param User $user      User entity
     * @param bool $published Post published
     *
     * @return Post Post entity
     */
    private function createPost(User $user, bool $published): Post
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
        $post->setPublished($published);

        $postRepository = self::$container->get(PostRepository::class);
        $postRepository->save($post);

        return $post;
    }
}