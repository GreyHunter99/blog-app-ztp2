<?php
/**
 * Tag Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class TagControllerTest.
 */
class TagControllerTest extends WebTestCase
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
     * Test index route.
     */
    public function testIndexRoute(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', '/tag/');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show tag.
     */
    public function testShowTag(): void
    {
        // given
        $expectedStatusCode = 200;

        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $tagRepository = self::$container->get(TagRepository::class);
        $tagRepository->save($expectedTag);

        // when
        $this->httpClient->request('GET', '/tag/'.$expectedTag->getId());
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test edit tag for non authorized user.
     */
    public function testEditTagNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 403;
        $user = $this->createUser([User::ROLE_USER]);
        $this->logIn($user);

        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $tagRepository = self::$container->get(TagRepository::class);
        $tagRepository->save($expectedTag);

        // when
        $this->httpClient->request('GET', '/tag/'.$expectedTag->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit tag for admin user.
     */
    public function testEditTagAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([User::ROLE_USER, User::ROLE_ADMIN]);
        $this->logIn($adminUser);

        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $tagRepository = self::$container->get(TagRepository::class);
        $tagRepository->save($expectedTag);

        // when
        $crawler = $this->httpClient->request('GET', '/tag/'.$expectedTag->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zapisz')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test delete tag for non authorized user.
     */
    public function testDeleteTagNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 403;
        $user = $this->createUser([User::ROLE_USER]);
        $this->logIn($user);

        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $tagRepository = self::$container->get(TagRepository::class);
        $tagRepository->save($expectedTag);

        // when
        $this->httpClient->request('GET', '/tag/'.$expectedTag->getId().'/delete');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete tag for admin user.
     */
    public function testDeleteTagAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([User::ROLE_USER, User::ROLE_ADMIN]);
        $this->logIn($adminUser);

        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $tagRepository = self::$container->get(TagRepository::class);
        $tagRepository->save($expectedTag);

        // when
        $crawler = $this->httpClient->request('GET', '/tag/'.$expectedTag->getId().'/delete');
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
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
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

        $userRepository = self::$container->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}