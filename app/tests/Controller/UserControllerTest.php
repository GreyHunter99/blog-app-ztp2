<?php
/**
 * User Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\UserData;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class UserControllerTest.
 */
class UserControllerTest extends WebTestCase
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
        $user = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($user);

        // when
        $this->httpClient->request('GET', '/user/');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test index route as admin user.
     */
    public function testIndexRouteAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser('user@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $this->logIn($adminUser);

        // when
        $this->httpClient->request('GET', '/user/');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show user profile search.
     */
    public function testShowUserSearch(): void
    {
        // given
        $expectedStatusCode = 200;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], false);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$expectedUser->getId());
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->filter('form')->form();
        $form['search']->setValue('query');
        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test show user profile as owner.
     */
    public function testShowUserOwner(): void
    {
        // given
        $expectedStatusCode = 200;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($expectedUser);

        // when
        $this->httpClient->request('GET', '/user/'.$expectedUser->getId());
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test show blocked user profile.
     */
    public function testShowUserBlocked(): void
    {
        // given
        $expectedStatusCode = 302;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], true);

        // when
        $this->httpClient->request('GET', '/user/'.$expectedUser->getId());
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test change password as non authorized user.
     */
    public function testChangePasswordNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com', [User::ROLE_USER], false);
        $user2 = $this->createUser('user2@example.com', [User::ROLE_USER], false);
        $this->logIn($user1);

        // when
        $this->httpClient->request('GET', '/user/'.$user2->getId().'/changePassword');
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test change password as authorized user.
     */
    public function testChangePasswordAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($expectedUser);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$expectedUser->getId().'/changePassword');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zmień')->form();
        $form['user_changePassword[password][first]']->setValue('password');
        $form['user_changePassword[password][second]']->setValue('password');
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test change data as non authorized user.
     */
    public function testChangeDataNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $user1 = $this->createUser('user1@example.com', [User::ROLE_USER], false);
        $user2 = $this->createUser('user2@example.com', [User::ROLE_USER], false);
        $this->logIn($user1);

        // when
        $this->httpClient->request('GET', '/user/'.$user2->getUserData()->getId().'/changeData');
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test change data as authorized user.
     */
    public function testChangeDataAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($expectedUser);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$expectedUser->getUserData()->getId().'/changeData');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zmień')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test grant admin as non authorized user.
     */
    public function testGrantAdminNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($expectedUser);

        // when
        $this->httpClient->request('GET', '/user/'.$expectedUser->getId().'/grantAdmin');
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test grant admin as admin user.
     */
    public function testGrantAdminAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser('admin@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $user = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($adminUser);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$user->getId().'/grantAdmin');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Nadaj')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test revoke admin as admin user.
     */
    public function testRevokeAdminAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser('admin@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $user = $this->createUser('user@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $this->logIn($adminUser);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$user->getId().'/grantAdmin');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Odbierz')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test revoke last admin as admin user.
     */
    public function testRevokeLastAdminAdminUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser('admin@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $this->logIn($adminUser);

        // when
        $this->httpClient->request('GET', '/user/'.$adminUser->getId().'/grantAdmin');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Ostatni administrator.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test block user as non authorized user.
     */
    public function testBlockUserNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $expectedUser = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($expectedUser);

        // when
        $this->httpClient->request('GET', '/user/'.$expectedUser->getId().'/block');
        $result = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $result);
    }

    /**
     * Test block user as admin user.
     */
    public function testBlockUserAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser('admin@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $user = $this->createUser('user@example.com', [User::ROLE_USER], false);
        $this->logIn($adminUser);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$user->getId().'/block');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Zablokuj')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test unblock user as admin user.
     */
    public function testUnblockUserAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser('admin@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $user = $this->createUser('user@example.com', [User::ROLE_USER], true);
        $this->logIn($adminUser);

        // when
        $crawler = $this->httpClient->request('GET', '/user/'.$user->getId().'/block');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $form = $crawler->selectButton('Odblokuj')->form();
        $this->httpClient->submit($form);
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Zaktualizowano pomyślnie.', $this->httpClient->getResponse()->getContent());
    }

    /**
     * Test block last admin as admin user.
     */
    public function testBlockLastAdminAdminUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser('admin@example.com', [User::ROLE_USER, User::ROLE_ADMIN], false);
        $this->logIn($adminUser);

        // when
        $this->httpClient->request('GET', '/user/'.$adminUser->getId().'/block');
        $result = $this->httpClient->getResponse()->getStatusCode();
        $this->httpClient->followRedirect();

        // then
        $this->assertEquals($expectedStatusCode, $result);
        $this->assertStringContainsString('Ostatni administrator.', $this->httpClient->getResponse()->getContent());
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
     * @param string $email   User email
     * @param array  $roles   User roles
     * @param bool   $blocked User blocked
     *
     * @return User User entity
     */
    private function createUser(string $email, array $roles, bool $blocked): User
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
        $user->setBlocked($blocked);
        $user->setUserData(new UserData());

        $userRepository = self::$container->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
