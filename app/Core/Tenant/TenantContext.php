<?php

namespace App\Core\Tenant;

use App\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    private bool $resolved = false;

    private bool $platformRequest = false;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->resolved = false;
        $this->platformRequest = false;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    public function resolved(): bool
    {
        return $this->resolved;
    }

    public function markPlatformRequest(bool $value = true): void
    {
        $this->platformRequest = $value;
    }

    public function isPlatformRequest(): bool
    {
        return $this->platformRequest;
    }

    public function requireId(): int
    {
        $id = $this->id();

        if ($id === null) {
            throw new \RuntimeException('No tenant is bound to the current request.');
        }

        return $id;
    }
}
