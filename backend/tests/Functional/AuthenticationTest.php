<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthenticationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $connection = self::getContainer()->get(Connection::class);
        self::assertStringEndsWith('_test', $connection->getDatabase());
        $connection->executeStatement('DELETE FROM app_user');
        self::getContainer()->get('cache.rate_limiter')->clear();
    }

    private function register(string $email = 'personne@example.test', string $password = 'UnMotDePasse-2026'): array
    {
        $this->client->jsonRequest('POST', '/api/auth/register', compact('email', 'password'));
        return $this->json();
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testRegistrationNormalizesEmailAndStoresOnlyAHash(): void
    {
        $data = $this->register(' Personne@EXAMPLE.test ');
        self::assertResponseStatusCodeSame(201);
        self::assertSame('personne@example.test', $data['email']);
        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('passwordHash', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $data['id']);
        $user = self::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        self::assertNotSame('UnMotDePasse-2026', $user->getPassword());
        self::assertTrue(self::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'UnMotDePasse-2026'));
    }

    public function testDuplicateEmailIsRejectedCaseInsensitively(): void
    {
        $this->register();
        $this->register('PERSONNE@example.test');
        self::assertResponseStatusCodeSame(409);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testInvalidFieldsAreRejected(): void
    {
        $data = $this->register('invalide', 'court');
        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('email', $data['errors']);
        self::assertArrayHasKey('password', $data['errors']);
    }

    public function testMalformedJsonIsRejected(): void
    {
        $this->client->request('POST', '/api/auth/register', server: ['CONTENT_TYPE' => 'application/json'], content: '{');
        self::assertResponseStatusCodeSame(400);
    }

    public function testWrongFieldTypesAreRejected(): void
    {
        $this->client->jsonRequest('POST', '/api/auth/register', ['email' => [], 'password' => 123]);
        self::assertResponseStatusCodeSame(400);
    }

    public function testUnsupportedContentTypeIsRejected(): void
    {
        $this->client->request('POST', '/api/auth/register', ['email' => 'personne@example.test']);
        self::assertResponseStatusCodeSame(415);
    }

    public function testLoginAndProtectedProfile(): void
    {
        $user = $this->register();
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => ' PERSONNE@example.test ', 'password' => 'UnMotDePasse-2026']);
        self::assertResponseIsSuccessful();
        $login = $this->json();
        self::assertSame('Bearer', $login['tokenType']);
        self::assertSame(3600, $login['expiresIn']);
        $this->client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$login['accessToken']]);
        self::assertResponseIsSuccessful();
        self::assertSame($user, $this->json());
        self::assertStringContainsString('no-store', $this->client->getResponse()->headers->get('Cache-Control'));
    }

    public function testInvalidCredentialsDoNotRevealWhetherAnAccountExists(): void
    {
        $this->register();
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => 'personne@example.test', 'password' => 'incorrect']);
        self::assertResponseStatusCodeSame(401);
        $known = $this->json();
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => 'inconnu@example.test', 'password' => 'incorrect']);
        self::assertResponseStatusCodeSame(401);
        self::assertSame($known, $this->json());
    }

    public function testMissingAndTamperedTokensAreRejected(): void
    {
        $this->client->request('GET', '/api/me');
        self::assertResponseStatusCodeSame(401);
        $this->client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer invalid.token.value']);
        self::assertResponseStatusCodeSame(401);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $this->register();
        $token = self::getContainer()->get(JWTEncoderInterface::class)->encode(['username' => 'personne@example.test', 'roles' => ['ROLE_USER'], 'iat' => time() - 7200, 'exp' => time() - 3600]);
        $this->client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginAttemptsAreLimited(): void
    {
        for ($i = 0; $i < 6; ++$i) {
            $this->client->jsonRequest('POST', '/api/auth/login', ['email' => 'inconnu@example.test', 'password' => 'incorrect']);
        }
        self::assertResponseStatusCodeSame(429);
        self::assertResponseHeaderSame('Retry-After', '60');
    }

    public function testRegistrationAttemptsAreLimited(): void
    {
        for ($i = 0; $i < 6; ++$i) $this->register('invalide', 'court');
        self::assertResponseStatusCodeSame(429);
    }

    public function testLongPasswordsAreNotTruncated(): void
    {
        $prefix = str_repeat('a', 80);
        $this->register(password: $prefix.'original');
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => 'personne@example.test', 'password' => $prefix.'different']);
        self::assertResponseStatusCodeSame(401);
        $this->client->jsonRequest('POST', '/api/auth/login', ['email' => 'personne@example.test', 'password' => $prefix.'original']);
        self::assertResponseIsSuccessful();
    }
}
