<?php

namespace App\Controller;

use App\Enum\McpServerType;
use App\Repository\McpServerRepository;
use App\Service\CredentialsEncryptor;
use App\Service\Mcp\McpServerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mcp')]
class McpEndpointController extends AbstractController
{
    private const PROTOCOL_VERSION = '2024-11-05';

    /** @var array<string, McpServerInterface> */
    private array $mcpServers;

    public function __construct(
        private readonly McpServerRepository $serverRepository,
        private readonly CredentialsEncryptor $encryptor,
        #[TaggedIterator('app.mcp_server')] iterable $mcpServers,
        private readonly LoggerInterface $logger,
        private readonly RateLimiterFactory $mcpEndpointLimiter,
        #[Autowire(param: 'app.version')] private readonly string $appVersion,
    ) {
        foreach ($mcpServers as $server) {
            $this->mcpServers[$server->getSupportedType()->value] = $server;
        }
    }

    #[Route('/{token}', name: 'app_mcp_endpoint', methods: ['POST'])]
    public function handle(string $token, Request $request): JsonResponse
    {
        $limiter = $this->mcpEndpointLimiter->create($request->getClientIp().':'.$token);
        if (!$limiter->consume()->isAccepted()) {
            return $this->jsonRpcError(null, -32029, 'Too many requests.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $server = $this->serverRepository->findOneByAccessToken($token);

        if (!$server) {
            return $this->jsonRpcError(null, -32001, 'Invalid token.', Response::HTTP_UNAUTHORIZED);
        }

        $authHeader = $request->headers->get('Authorization');
        if (!hash_equals('Bearer '.$server->getClientSecret(), (string) $authHeader)) {
            return $this->jsonRpcError(null, -32001, 'Invalid token.', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->jsonRpcError(null, -32700, 'Parse error.');
        }

        if (!$body || !isset($body['jsonrpc'], $body['method'])) {
            return $this->jsonRpcError(null, -32600, 'Invalid JSON-RPC request.');
        }

        if ('2.0' !== $body['jsonrpc']) {
            return $this->jsonRpcError($body['id'] ?? null, -32600, 'Invalid JSON-RPC version.');
        }

        $id = $body['id'] ?? null;
        $method = $body['method'];
        $params = $body['params'] ?? [];

        try {
            $rawCredentials = $this->encryptor->decrypt($server->getEncryptedCredentials());
        } catch (\RuntimeException $e) {
            $this->logger->error('Credential decryption failed for MCP server {id}.', [
                'id' => $server->getId(),
                'exception' => $e,
            ]);

            return $this->jsonRpcError($id, -32000, 'Server error.');
        }

        $mcpServer = $this->resolveMcpServer($server->getType());

        return match ($method) {
            'initialize' => $this->handleInitialize($id, $server->getName()),
            'tools/list' => $this->handleToolsList($id, $mcpServer, $rawCredentials),
            'tools/call' => $this->handleToolsCall($id, $params, $rawCredentials, $mcpServer),
            default => $this->jsonRpcError($id, -32601, "Unknown method: {$method}"),
        };
    }

    private function resolveMcpServer(McpServerType $type): McpServerInterface
    {
        return $this->mcpServers[$type->value]
            ?? throw new \LogicException("No MCP server registered for type '{$type->value}'.");
    }

    private function handleInitialize(mixed $id, string $serverName): JsonResponse
    {
        return $this->jsonRpcResult($id, [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => []],
            'serverInfo' => ['name' => $serverName, 'version' => $this->appVersion],
        ]);
    }

    private function handleToolsList(mixed $id, McpServerInterface $mcpServer, array $rawCredentials): JsonResponse
    {
        return $this->jsonRpcResult($id, ['tools' => $mcpServer->getToolDefinitions($rawCredentials)]);
    }

    private function handleToolsCall(mixed $id, array $params, array $rawCredentials, McpServerInterface $mcpServer): JsonResponse
    {
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!$toolName) {
            return $this->jsonRpcError($id, -32602, 'Missing "name" parameter.');
        }

        try {
            $result = $mcpServer->callTool($toolName, $arguments, $rawCredentials);

            return $this->jsonRpcResult($id, [
                'content' => [['type' => 'text', 'text' => $result]],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonRpcError($id, -32601, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('MCP tool call failed.', ['exception' => $e]);

            return $this->jsonRpcError($id, -32000, 'Server error.');
        }
    }

    private function jsonRpcResult(mixed $id, array $result): JsonResponse
    {
        return new JsonResponse(['jsonrpc' => '2.0', 'result' => $result, 'id' => $id]);
    }

    private function jsonRpcError(mixed $id, int $code, string $message, int $httpStatus = 200): JsonResponse
    {
        return new JsonResponse(
            ['jsonrpc' => '2.0', 'error' => ['code' => $code, 'message' => $message], 'id' => $id],
            $httpStatus,
        );
    }
}
