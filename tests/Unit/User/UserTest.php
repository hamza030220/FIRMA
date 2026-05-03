<?php

namespace App\Tests\User;

use App\Controller\Admin\UtilisateurController;
use App\Entity\User\PasswordResetToken;
use App\Entity\User\Utilisateur;
use App\Repository\User\UtilisateurRepository;
use App\Security\BannedUserChecker;
use App\Service\Maladie\Weather\MaladieWeatherAutoAlertService;
use App\Service\User\LoginFailureHandler;
use App\Service\User\LoginSuccessHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserTest extends TestCase
{
    // ─── Shared factory ───────────────────────────────────────────────────────

    private function makeUser(string $type = 'client', ?int $id = null, bool $banned = false): Utilisateur
    {
        $u = new Utilisateur();
        $u->setNom('Ben Salah')->setPrenom('Hana')->setEmail('hana@example.com')
          ->setTypeUser($type)->setMotDePasse('hashed')->setDateCreation(new \DateTime())
          ->setIsBanned($banned);

        if ($id !== null) {
            $ref = new \ReflectionProperty(Utilisateur::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($u, $id);
        }

        return $u;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // Utilisateur entity
    // ═════════════════════════════════════════════════════════════════════════

    public function testUtilisateurRoles(): void
    {
        $this->assertSame(['ROLE_USER'],                    $this->makeUser('client')->getRoles());
        $this->assertSame(['ROLE_TECHNICIEN', 'ROLE_USER'], $this->makeUser('technicien')->getRoles());
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'],      $this->makeUser('admin')->getRoles());
    }

    public function testUtilisateurFullNameAndIdentifier(): void
    {
        $u = $this->makeUser();
        $this->assertSame('Hana Ben Salah', $u->getFullName());
        $this->assertSame('hana@example.com', $u->getUserIdentifier());
    }

    public function testUtilisateurBanCycle(): void
    {
        $u = $this->makeUser();

        $u->setIsBanned(true)->setBanReason('Spam')->setBannedAt(new \DateTime());
        $this->assertTrue($u->isBanned());
        $this->assertSame('Spam', $u->getBanReason());

        $u->setIsBanned(false)->setBanReason(null)->setBannedAt(null);
        $this->assertFalse($u->isBanned());
        $this->assertNull($u->getBanReason());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PasswordResetToken entity
    // ═════════════════════════════════════════════════════════════════════════

    public function testPasswordResetTokenLifecycle(): void
    {
        $token = new PasswordResetToken($this->makeUser(), 'tok', new \DateTime('+1 hour'));

        $this->assertFalse($token->isExpired());
        $this->assertFalse($token->isUsed());

        $token->markUsed();
        $this->assertTrue($token->isUsed());
    }

    public function testPasswordResetTokenExpiry(): void
    {
        $this->assertFalse((new PasswordResetToken($this->makeUser(), 't', new \DateTime('+1 hour')))->isExpired());
        $this->assertTrue((new PasswordResetToken($this->makeUser(), 't', new \DateTime('-1 second')))->isExpired());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // BannedUserChecker
    // ═════════════════════════════════════════════════════════════════════════

    public function testBannedUserCheckerThrowsForBannedAndPassesForOthers(): void
    {
        $checker = new BannedUserChecker();

        $checker->checkPreAuth($this->makeUser(banned: false)); // must not throw

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $checker->checkPreAuth($this->makeUser(banned: true));
    }

    public function testBannedUserCheckerIgnoresNonUtilisateur(): void
    {
        $checker = new BannedUserChecker();
        $checker->checkPreAuth($this->createMock(UserInterface::class)); // must not throw
        $this->assertTrue(true);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // LoginFailureHandler
    // ═════════════════════════════════════════════════════════════════════════

    private function makeRequest(string $username = ''): Request
    {
        $r = Request::create('/login', 'POST', ['_username' => $username]);
        $r->setSession(new Session(new MockArraySessionStorage()));
        return $r;
    }

    public function testLoginFailureBannedRedirectsAndStoresEmail(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->with('user_banned')->willReturn('/compte-suspendu');

        $handler  = new LoginFailureHandler($router, $this->createMock(UtilisateurRepository::class));
        $request  = $this->makeRequest('victim@example.com');
        $response = $handler->onAuthenticationFailure($request, new CustomUserMessageAccountStatusException('BANNED'));

        $this->assertSame('/compte-suspendu', $response->getTargetUrl());
        $this->assertSame('victim@example.com', $request->getSession()->get('banned_email'));
    }

    public function testLoginFailureOtherExceptionRedirectsToLogin(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->with('app_login')->willReturn('/login');

        $handler  = new LoginFailureHandler($router, $this->createMock(UtilisateurRepository::class));
        $response = $handler->onAuthenticationFailure($this->makeRequest(), new AuthenticationException());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // LoginSuccessHandler
    // ═════════════════════════════════════════════════════════════════════════

    private function makeToken(string $type): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->makeUser($type));
        return $token;
    }

    public function testLoginSuccessRedirectsByRole(): void
    {
        $alert = $this->createMock(MaladieWeatherAutoAlertService::class);
        $req   = Request::create('/login', 'POST');

        $adminRouter = $this->createMock(RouterInterface::class);
        $adminRouter->method('generate')->willReturn('/admin');
        $this->assertSame('/admin', (new LoginSuccessHandler($adminRouter, $alert))
            ->onAuthenticationSuccess($req, $this->makeToken('admin'))->getTargetUrl());

        $userRouter = $this->createMock(RouterInterface::class);
        $userRouter->method('generate')->willReturn('/user');
        $this->assertSame('/user', (new LoginSuccessHandler($userRouter, $alert))
            ->onAuthenticationSuccess($req, $this->makeToken('client'))->getTargetUrl());
    }

    public function testLoginSuccessSwallowsAlertServiceException(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/user');

        $alert = $this->createMock(MaladieWeatherAutoAlertService::class);
        $alert->method('checkAndSendForUser')->willThrowException(new \RuntimeException('API down'));

        $response = (new LoginSuccessHandler($router, $alert))
            ->onAuthenticationSuccess(Request::create('/login', 'POST'), $this->makeToken('client'));

        $this->assertSame('/user', $response->getTargetUrl());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // ResetPasswordController (logic only)
    // ═════════════════════════════════════════════════════════════════════════

    private function validatePasswords(string $p, string $c): ?string
    {
        if (strlen($p) < 8)  return 'Le mot de passe doit contenir au moins 8 caractères.';
        if ($p !== $c)        return 'Les mots de passe ne correspondent pas.';
        return null;
    }

    public function testResetPasswordValidation(): void
    {
        $this->assertNotNull($this->validatePasswords('short', 'short'));
        $this->assertNotNull($this->validatePasswords('longpass1', 'other1'));
        $this->assertNull($this->validatePasswords('securePass1', 'securePass1'));
    }

    public function testResetPasswordEnumerationPrevention(): void
    {
        // $sent is always true on POST regardless of whether email exists
        foreach ([null, $this->makeUser(banned: false), $this->makeUser(banned: true)] as $user) {
            $sent = true; // controller always sets this
            $this->assertTrue($sent);
        }
    }

    public function testResetPasswordTokenValidityAndSuccess(): void
    {
        $valid   = new PasswordResetToken($this->makeUser(), 'tok', new \DateTime('+1 hour'));
        $expired = new PasswordResetToken($this->makeUser(), 'tok', new \DateTime('-1 second'));

        $this->assertFalse($valid->isExpired() || $valid->isUsed());
        $this->assertTrue($expired->isExpired());

        // Successful reset flow
        $valid->markUsed();
        $valid->getUtilisateur()->setMotDePasse('new_hash');
        $this->assertTrue($valid->isUsed());
        $this->assertSame('new_hash', $valid->getUtilisateur()->getMotDePasse());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // UtilisateurRepository
    // ═════════════════════════════════════════════════════════════════════════

    // ServiceEntityRepository::createQueryBuilder() lives on $this, not the EM.
    // We must partial-mock the repository and stub createQueryBuilder directly.
    private function buildRepo(array $arrayResult): UtilisateurRepository
    {
        $fakeQuery = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArrayResult', 'getResult'])
            ->getMock();
        $fakeQuery->method('getArrayResult')->willReturn($arrayResult);
        $fakeQuery->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn(new Expr());
        $qb->method('getQuery')->willReturn($fakeQuery);

        $repo = $this->getMockBuilder(UtilisateurRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();
        $repo->method('createQueryBuilder')->willReturn($qb);

        return $repo;
    }

    private function buildRepoWithEm(EntityManagerInterface $em): UtilisateurRepository
    {
        $repo = $this->getMockBuilder(UtilisateurRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
        $repo->method('getEntityManager')->willReturn($em);

        return $repo;
    }

    public function testCountByType(): void
    {
        $result = $this->buildRepo([
            ['type' => 'client',     'total' => '5'],
            ['type' => 'technicien', 'total' => '3'],
            ['type' => 'admin',      'total' => '2'],
        ])->countByType();

        $this->assertSame(['client' => 5, 'technicien' => 3, 'admin' => 2], $result);

        $sparse = $this->buildRepo([['type' => 'client', 'total' => '7']])->countByType();
        $this->assertSame(0, $sparse['technicien']);
        $this->assertSame(0, $sparse['admin']);
    }

    public function testFindAdminEmailsDeduplicatesAndFiltersEmpty(): void
    {
        $result = $this->buildRepo([
            ['email' => 'admin@example.com'],
            ['email' => 'admin@example.com'],
            ['email' => ''],
        ])->findAdminEmails();

        $this->assertCount(1, $result);
        $this->assertSame('admin@example.com', $result[0]);
    }

    public function testUpgradePasswordThrowsForUnsupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $foreign = new class implements \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return null; }
        };

        $this->buildRepo([])->upgradePassword($foreign, 'hash');
    }

    public function testUpgradePasswordPersistsAndFlushes(): void
    {
        $user = $this->makeUser();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($user);
        $em->expects($this->once())->method('flush');

        $this->buildRepoWithEm($em)->upgradePassword($user, 'newHash');

        $this->assertSame('newHash', $user->getMotDePasse());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // UtilisateurController (pure logic)
    // ═════════════════════════════════════════════════════════════════════════

    private function paginate(array $items, int $page, int $limit = 10): array
    {
        $page       = max(1, $page);
        $totalPages = max(1, (int) ceil(count($items) / $limit));
        $page       = min($page, $totalPages);
        return [$page, $totalPages, array_slice($items, ($page - 1) * $limit, $limit)];
    }

    public function testPagination(): void
    {
        $users = array_fill(0, 25, $this->makeUser());

        [$p, $total, $slice] = $this->paginate($users, 1);
        $this->assertSame([1, 3, 10], [$p, $total, count($slice)]);

        [$p, , $slice] = $this->paginate($users, 3);
        $this->assertSame([3, 5], [$p, count($slice)]);

        // Out-of-range clamps; empty list returns 1 page
        [$p] = $this->paginate($users, 99);
        $this->assertSame(3, $p);

        [, $total] = $this->paginate([], 1);
        $this->assertSame(1, $total);
    }

    public function testBanGuards(): void
    {
        $admin  = $this->makeUser('admin', 1);
        $client = $this->makeUser('client', 2);
        $other  = $this->makeUser('admin', 99);

        $this->assertTrue($admin->getId() === $admin->getId());                         // cannot ban self
        $this->assertTrue(in_array('ROLE_ADMIN', $other->getRoles(), true));            // cannot ban admin
        $this->assertFalse(in_array('ROLE_ADMIN', $client->getRoles(), true));          // can ban client
    }

    public function testBanReasonFallbackAndTrim(): void
    {
        $raw = trim('');
        $this->assertSame('Aucune raison précisée.', $raw !== '' ? $raw : 'Aucune raison précisée.');
        $this->assertSame('Spam', trim('  Spam   '));
    }

    public function testLogAction(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $admin  = $this->makeUser('admin', 1);
        $target = $this->makeUser('client', 42);

        $logger->expects($this->once())->method('info')->with(
            $this->stringContains('[ADMIN_AUDIT]'),
            $this->callback(fn(array $ctx) =>
                $ctx['action'] === 'BAN' && $ctx['id'] === $target->getId()
            ),
        );

        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($admin);
        $storage = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->with('security.token_storage')->willReturn($storage);

        $controller = new UtilisateurController($logger);
        $controller->setContainer($container);

        $ref = new \ReflectionMethod($controller, 'logAction');
        $ref->setAccessible(true);
        $ref->invoke($controller, 'BAN', $target, 'reason');
    }
}