<?php

namespace App\Core\Module;

readonly class ModuleManifest
{
    /**
     * @param  list<string>  $dependencies
     */
    public function __construct(
        public string $name,
        public string $code,
        public string $version,
        public string $description,
        public bool $isCore,
        public array $dependencies,
        public string $path,
    ) {}

    public static function fromArray(array $data, string $path): self
    {
        return new self(
            name: (string) ($data['name'] ?? basename($path)),
            code: (string) ($data['code'] ?? strtolower(basename($path))),
            version: (string) ($data['version'] ?? '1.0.0'),
            description: (string) ($data['description'] ?? ''),
            isCore: (bool) ($data['is_core'] ?? false),
            dependencies: array_values($data['dependencies'] ?? []),
            path: $path,
        );
    }
}
