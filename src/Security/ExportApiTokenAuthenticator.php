<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Autentica el firewall "export_api" mediante un token compartido (header X-Api-Token).
 * Si EXPORT_ALLOWED_IPS no está vacío, además exige que la IP del cliente esté en esa lista.
 */
class ExportApiTokenAuthenticator extends AbstractAuthenticator
{
    private const HEADER = 'X-Api-Token';

    /** @var string[] */
    private array $allowedIps;

    public function __construct(
        private readonly string $apiToken,
        string $allowedIps,
    ) {
        $this->allowedIps = array_values(array_filter(array_map('trim', explode(',', $allowedIps))));
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        if ($this->allowedIps !== [] && !in_array($request->getClientIp(), $this->allowedIps, true)) {
            throw new CustomUserMessageAuthenticationException('IP no autorizada.');
        }

        if ($this->apiToken === '' || !hash_equals($this->apiToken, $request->headers->get(self::HEADER, ''))) {
            throw new CustomUserMessageAuthenticationException('Token inválido.');
        }

        return new SelfValidatingPassport(
            new UserBadge('export-client', fn () => new InMemoryUser('export-client', null, ['ROLE_API_EXPORT']))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }
}
