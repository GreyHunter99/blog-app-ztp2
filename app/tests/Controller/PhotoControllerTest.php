<?php
/**
 * Photo controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Photo;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PhotoRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class PhotoControllerTest.
 */
class PhotoControllerTest extends WebTestCase
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
        $user1 = $this->createUser('user1@example.com');
        $post = $this->createPost($user1);

        $user2 = $this->createUser('user2@example.com');
        $this->logIn($user2);

        // when
        $this->httpClient->request('GET', '/photo/'.$post->getId().'/');
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
        $user = $this->createUser('user@example.com');
        $this->logIn($user);
        $post = $this->createPost($user);

        // when
        $this->httpClient->request('GET', '/photo/'.$post->getId().'/');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show photo as non authorized user.
     */
    public function testShowPhotoNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com');
        $post = $this->createPost($user1);
        $photo = $this->createPhoto($post);

        $user2 = $this->createUser('user2@example.com');
        $this->logIn($user2);

        // when
        $this->httpClient->request('GET', '/photo/view/'.$photo->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show photo as authorized user.
     */
    public function testShowPhotoAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser('user@example.com');
        $this->logIn($user);
        $post = $this->createPost($user);
        $photo = $this->createPhoto($post);

        // when
        $this->httpClient->request('GET', '/photo/view/'.$photo->getId());
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test create photo as non authorized user.
     */
    public function testCreatePhotoNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com');
        $post = $this->createPost($user1);

        $user2 = $this->createUser('user2@example.com');
        $this->logIn($user2);

        // when
        $this->httpClient->request('GET', '/photo/'.$post->getId().'/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test create photo for authorized user.
     */
    public function testCreatePhotoAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser('user@example.com');
        $this->logIn($user);
        $post = $this->createPost($user);

        $file = new UploadedFile(__DIR__.'/../fixtures/test1.jpg', 'test1.jpg');

        // when
        $crawler = $this->httpClient->request('GET', '/photo/'.$post->getId().'/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Stwórz')->form();
        $form['photo[file]']->upload($file);
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Stworzono pomyślnie.', $this->httpClient->getResponse()->getContent());

        $photoRepository = self::$container->get(PhotoRepository::class);
        $filesystem = new Filesystem();
        $photo = $photoRepository->findOneByPost($post);
        $filesystem->remove(__DIR__.'/../../public/uploads/photos/'.$photo->getFilename());
    }

    /**
     * Test edit photo as non authorized user.
     */
    public function testEditPhotoNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com');
        $post = $this->createPost($user1);
        $photo = $this->createPhoto($post);

        $user2 = $this->createUser('user2@example.com');
        $this->logIn($user2);

        // when
        $this->httpClient->request('GET', '/photo/'.$photo->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit photo for authorized user.
     */
    public function testEditPhotoAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser('user@example.com');
        $this->logIn($user);
        $post = $this->createPost($user);
        $photo = $this->createPhoto($post);

        $file = new UploadedFile(__DIR__.'/../fixtures/test1.jpg', 'test1.jpg');

        // when
        $crawler = $this->httpClient->request('GET', '/photo/'.$photo->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zapisz')->form();
        $form['photo[file]']->upload($file);
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());

        $photoRepository = self::$container->get(PhotoRepository::class);
        $filesystem = new Filesystem();
        $photo = $photoRepository->findOneByPost($post);
        $filesystem->remove(__DIR__.'/../../public/uploads/photos/'.$photo->getFilename());
    }

    /**
     * Test delete photo as non authorized user.
     */
    public function testDeletePhotoNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com');
        $post = $this->createPost($user1);
        $photo = $this->createPhoto($post);

        $user2 = $this->createUser('user2@example.com');
        $this->logIn($user2);

        // when
        $this->httpClient->request('GET', '/photo/'.$photo->getId().'/delete');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete photo for authorized user.
     */
    public function testDeletePhotoAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser('user@example.com');
        $this->logIn($user);
        $post = $this->createPost($user);
        $photo = $this->createPhoto($post);

        // when
        $crawler = $this->httpClient->request('GET', '/photo/'.$photo->getId().'/delete');
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
     * Create photo.
     *
     * @param Post $post Post published
     *
     * @return Photo Photo entity
     */
    private function createPhoto(Post $post): Photo
    {
        $photo = new Photo();
        $photo->setFilename(__DIR__.'/../fixtures/test1.jpg');
        $photo->setPost($post);

        $photoRepository = self::$container->get(PhotoRepository::class);
        $photoRepository->save($photo);

        return $photo;
    }
}
