<?php

declare(strict_types=1);

use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;

require_once './src/controller/mongoDB.php';

function haveAuthHeader(): bool
{
    $header = apache_request_headers();
    if (gettype($header) == "boolean") {
        return false;
    }
    return isset($header["Authorization"]) ? str_starts_with($header["Authorization"], "Bearer ") && trim($header["Authorization"]) != "Bearer" : false;
}

function checkJWT(): bool
{
    $header = apache_request_headers();
    if (!haveAuthHeader()) {
        return false;
    }
    //check jwt here
    $jwtToken = trim(explode(" ", $header["Authorization"], 2)[1]);
    if (trim($jwtToken) != "") {
        $claim = getClaimsJWT(trim($jwtToken));
        $mongoDB = new MongoDBController();
        return $mongoDB->knowUser($claim["mail"], $claim["password"]);
    }

    return false;
}

function getClaimsJWT(string $jwt): array
{
    $parser = new Parser(new JoseEncoder());

    try {
        $token = $parser->parse($jwt);
    } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
        echo 'Oh no, an error: ' . $e->getMessage();
    }
    assert($token instanceof UnencryptedToken);
    $value = [
        'mail' => $token->claims()->get('mail'),
        'password' => $token->claims()->get('password'),
        'role' => $token->claims()->get('role')
    ];
    return $value;
}
