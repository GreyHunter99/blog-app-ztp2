<?php
/**
 * Security Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class SecurityControllerTest.
 */
class SecurityControllerTest extends WebTestCase
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
     * Test login.
     */
    public function testLogin(): void
    {
        // given
        $expectedStatusCode = 200;
        $this->createUser();

        // when
        $crawler = $this->httpClient->request('GET', '/login');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zaloguj się')->form();
        $form['email']->setValue('user@example.com');
        $form['password']->setValue('p@55w0rd');
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Zalogowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test login as user.
     */
    public function testLoginUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser();
        $this->logIn($user);

        // when
        $this->httpClient->request('GET', '/login');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test logout.
     */
    public function testLogout(): void
    {
        // given
        $expectedStatusCode = 302;
        $user = $this->createUser();
        $this->logIn($user);

        // when
        $this->httpClient->request('GET', '/logout');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->httpClient->followRedirect();
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
        $this->assertStringContainsString('Wylogowano pomyślnie.', $this->httpClient->getResponse()->getContent());
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
}