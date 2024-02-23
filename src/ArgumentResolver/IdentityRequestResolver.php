<?php

namespace Peekabooauth\PeekabooBundle\ArgumentResolver;

use Peekabooauth\PeekabooBundle\DTO\IdentityRequestDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IdentityRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === IdentityRequestDTO::class;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $identityRequestDTO = new IdentityRequestDTO($request->query->all());
        $errors = $this->validator->validate($identityRequestDTO);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string)$errors);
        }

        yield $identityRequestDTO;
    }
}
