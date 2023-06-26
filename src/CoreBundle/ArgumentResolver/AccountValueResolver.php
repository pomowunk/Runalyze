<?php

namespace Runalyze\Bundle\CoreBundle\ArgumentResolver;

use App\Entity\Account;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AccountValueResolver implements ArgumentValueResolverInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return Account::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        yield $this->tokenStorage->getToken()->getUser() ?? null;
    }
}
