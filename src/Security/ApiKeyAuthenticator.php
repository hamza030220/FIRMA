<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticates requests that carry the X-API-KEY header.
 * The key is compared against the INTEGRATION_API_KEY env variable.
 */
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly string $apiKey) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $provided = $request->headers->get('X-API-KEY', '');

        if (!hash_equals($this->apiKey, $provided)) {
            throw new CustomUserMessageAuthenticationException('Invalid API key.');
        }

        // Use a synthetic "api_client" user identity — no DB lookup needed.
        return new SelfValidatingPassport(new UserBadge('api_client'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // let the request continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['success' => false, 'message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
