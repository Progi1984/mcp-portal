<?php

namespace App\Controller;

use App\Entity\McpServer;
use App\Enum\McpServerType;
use App\Service\ConnectorTestService;
use App\Service\CredentialsEncryptor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ConnectorTestController extends AbstractController
{
    public function __construct(
        private readonly ConnectorTestService $testService,
        private readonly CredentialsEncryptor $encryptor,
    ) {
    }

    /** Test with raw credentials from the form (before saving). */
    #[Route('/api/{type}/test', name: 'app_connector_test_raw', methods: ['POST'],
        requirements: ['type' => '[a-z_]+'])]
    public function testRaw(McpServerType $type, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->testService->test($type, $data);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
    }

    /** Test a saved MCP server (from the project page). */
    #[Route('/api/{type}/test/{id}', name: 'app_connector_test_server', methods: ['GET'],
        requirements: ['type' => '[a-z_]+'])]
    public function testServer(McpServer $server): JsonResponse
    {
        $owner = $server->getProject()->getUser();
        if ($owner !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $raw = $this->encryptor->decrypt($server->getEncryptedCredentials());
        } catch (\RuntimeException) {
            return new JsonResponse(['success' => false, 'message' => 'Credential decryption failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->testService->test($server->getType(), $raw);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
    }
}
