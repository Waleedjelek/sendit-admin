<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Kernel;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Ramsey\Uuid\Uuid;

class JWTService extends BaseService
{
    private string $issuer = 'jwt.senditworld.com/';

    private string $permittedFor = 'admin.senditworld.com';

    private int $lifetime = 60 * 60 * 24;

    private Configuration $configuration;

    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            LocalFileReference::file($this->kernel->getProjectDir().'/config/jwt/private.pem'),
            LocalFileReference::file($this->kernel->getProjectDir().'/config/jwt/public.pem'),
        );
    }

    public function createToken(array $claims): Plain
    {
        $issuedAt = new \DateTimeImmutable();

        $builder = $this->configuration->builder();
        $builder->issuedBy($this->issuer);
        $builder->permittedFor($this->permittedFor);
        $builder->identifiedBy(Uuid::uuid4()->toString());
        $builder->issuedAt($issuedAt);
        $builder->canOnlyBeUsedAfter($issuedAt);
        $builder->expiresAt($issuedAt->modify("+{$this->lifetime} seconds"));

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder->getToken(
            $this->getConfiguration()->signer(),
            $this->getConfiguration()->signingKey()
        );
    }

    public function getToken(string $jwt): Plain
    {
        return $this->getConfiguration()->parser()->parse($jwt);
    }

    public function validateToken(Token $token): bool
    {
        $clock = new FrozenClock(new \DateTimeImmutable());

        return $this->configuration->validator()->validate($token,
            new IssuedBy($this->issuer),
            new PermittedFor($this->permittedFor),
            new LooseValidAt($clock)
        );
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function setIssuer(string $issuer): self
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getPermittedFor(): string
    {
        return $this->permittedFor;
    }

    public function setPermittedFor(string $permittedFor): self
    {
        $this->permittedFor = $permittedFor;

        return $this;
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function setLifetime(int $lifetime): self
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}
