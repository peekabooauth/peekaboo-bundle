<?php

namespace Peekabooauth\PeekabooBundle\DTO;

use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

class IdentityRequestDTO implements JsonSerializable
{
    #[Assert\NotBlank(message: 'Field name can not be empty')]
    public ?string $redirectUrl = null;

    #[Assert\NotBlank(message: 'Field app can not be empty')]
    public ?string $app = null;

    #[Assert\NotBlank(message: 'Field signature can not be empty')]
    public ?string $signature = null;

    #[Assert\NotBlank(message: 'Field autologin can not be empty')]
    public ?string $autologin = null;

    public function __construct(array $data)
    {
        $this->redirectUrl = $data['redirect_url'] ?? null;
        $this->app = $data['app'] ?? null;
        $this->signature = $data['signature'] ?? null;
        $this->autologin = $data['autologin'] ?? null;
    }

    public function jsonSerialize(): array
    {
        return [
            'redirect_url' => $this->redirectUrl,
            'app' => $this->app,
            'signature' => $this->signature,
            'autologin' => $this->autologin,
        ];
    }
}
