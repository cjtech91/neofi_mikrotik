<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Mikrotik\RouterOSApiClient;
use App\Repositories\AuditRepository;
use App\Repositories\DeviceRepository;
use App\Security\Crypto;

final class DeviceController
{
    public function index(Request $request, array $params): Response
    {
        $devices = (new DeviceRepository())->all();
        return Response::json(['data' => $devices]);
    }

    public function store(Request $request, array $params): Response
    {
        $body = $request->json();

        $name = isset($body['name']) ? (string) $body['name'] : '';
        $host = isset($body['host']) ? (string) $body['host'] : '';
        $username = isset($body['username']) ? (string) $body['username'] : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';
        $apiPort = isset($body['api_port']) ? (int) $body['api_port'] : 8728;
        $useSsl = isset($body['use_ssl']) ? (bool) $body['use_ssl'] : false;

        if ($name === '' || $host === '' || $username === '' || $password === '') {
            return Response::json(['error' => 'name, host, username, password are required'], 422);
        }

        $passwordCiphertext = Crypto::encrypt($password);
        $repo = new DeviceRepository();
        $id = $repo->create([
            'name' => $name,
            'host' => $host,
            'api_port' => $apiPort,
            'use_ssl' => $useSsl,
            'username' => $username,
            'password_ciphertext' => $passwordCiphertext,
        ]);

        (new AuditRepository())->log($id, 'devices.create', [
            'name' => $name,
            'host' => $host,
            'api_port' => $apiPort,
            'use_ssl' => $useSsl,
            'username' => $username,
        ]);

        return Response::json(['id' => $id], 201);
    }

    public function testConnection(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            return Response::json(['error' => 'Invalid device id'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($id);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $password = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $password);
            $identity = $client->commandOrThrow('/system/identity/print');
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }

            (new AuditRepository())->log($id, 'devices.test_connection.failed', [
                'error' => $e->getMessage(),
            ]);

            return Response::json(['ok' => false, 'error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($id, 'devices.test_connection.ok', [
            'identity' => $identity['name'] ?? null,
        ]);

        return Response::json([
            'ok' => true,
            'identity' => $identity,
        ]);
    }
}
