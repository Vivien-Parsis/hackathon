<?php
declare(strict_types=1);

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Builder;

require 'vendor/autoload.php';

function createToken (string $mail, string $nom, string $password, string $role):string{
    
    $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
    $algorithm    = new Sha256();
    $signingKey   = InMemory::plainText(random_bytes(32));
    
    $tokenJWT = $tokenBuilder
        ->withClaim('name', $nom)
        ->withClaim('mail', $mail)
        ->withClaim('password', $password)
        ->withClaim('role', $role)
        ->withHeader('type', 'jwt')
        ->getToken($algorithm, $signingKey);
    
    return $tokenJWT->toString();

}

