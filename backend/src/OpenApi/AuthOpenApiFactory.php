<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

#[AsDecorator('api_platform.openapi.factory', priority: -100)]
final class AuthOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        #[Autowire('%kernel.project_dir%/config/openapi/auth.yaml')]
        private string $contractPath,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $api = ($this->decorated)($context);
        $paths = new Model\Paths();
        foreach ($api->getPaths()->getPaths() as $path => $item) {
            if (str_starts_with($path, '/')) $paths->addPath($path, $item);
        }
        $api = $api->withPaths($paths);
        $contract = Yaml::parseFile($this->contractPath);
        foreach ($contract['components']['schemas'] as $name => $schema) {
            $api->getComponents()->getSchemas()[$name] = $schema;
        }
        foreach ($contract['components']['responses'] as $name => $response) {
            $api->getComponents()->getResponses()[$name] = $response;
        }
        $api->getComponents()->getSecuritySchemes()['bearerAuth'] = new \ArrayObject($contract['components']['securitySchemes']['bearerAuth']);
        foreach ($contract['paths'] as $path => $methods) {
            $item = new Model\PathItem();
            foreach ($methods as $method => $definition) {
                $body = $definition['requestBody'] ?? null;
                $operation = new Model\Operation(
                    operationId: $definition['operationId'],
                    tags: [match ($path) {
                        '/health' => 'État du service',
                        '/files' => 'Fichiers',
                        default => 'Authentification',
                    }],
                    responses: $definition['responses'],
                    summary: $definition['summary'],
                    description: $definition['description'] ?? null,
                    requestBody: $body ? new Model\RequestBody(content: new \ArrayObject($body['content']), required: $body['required']) : null,
                    security: $definition['security'] ?? [],
                );
                $item = $item->{'with'.ucfirst($method)}($operation);
            }
            $api->getPaths()->addPath('/api'.$path, $item);
        }

        return $api->withServers([new Model\Server('/')]);
    }
}
