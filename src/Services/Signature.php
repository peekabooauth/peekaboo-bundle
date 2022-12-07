<?php

namespace Peekabooauth\PeekabooBundle\Services;

class Signature
{
    public function generateSignature(array $data, string $secret): string
    {
        return md5(implode(',' , $data) . $secret);
    }
}
