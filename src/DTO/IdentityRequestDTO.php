<?php

namespace Peekabooauth\PeekabooBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class IdentityRequestDTO implements \JsonSerializable
{
    #[Assert\NotBlank(message: 'Field name can not be empty')]
    public ?string $redirectUrl = null;

    public ?string $app = null;

    public function __construct(array $data)
    {
        $this->redirectUrl = $data['redirect_url'] ?? null;
        $this->app = $data['app'] ?? null;
    }

    public function jsonSerialize(): array
    {
        return [
            'redirect_url' => $this->redirectUrl,
            'app' => $this->app
        ];
    }
}
