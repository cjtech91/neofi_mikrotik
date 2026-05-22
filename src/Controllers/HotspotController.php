<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Mikrotik\RouterOSApiClient;
use App\Repositories\AuditRepository;
use App\Repositories\DeviceRepository;
use App\Security\Crypto;

final class HotspotController
{
    public function listUsers(Request $request, array $params): Response
    {
        $deviceId = (int) ($params['id'] ?? 0);
        if ($deviceId <= 0) {
            return Response::json(['error' => 'Invalid device id'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $apiPassword);
            $rows = $client->rows('/ip/hotspot/user/print');
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }
            return Response::json(['error' => $e->getMessage()], 502);
        }

        return Response::json(['data' => $rows]);
    }

    public function createUser(Request $request, array $params): Response
    {
        $deviceId = (int) ($params['id'] ?? 0);
        if ($deviceId <= 0) {
            return Response::json(['error' => 'Invalid device id'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $body = $request->json();
        $name = isset($body['name']) ? (string) $body['name'] : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';
        $profile = isset($body['profile']) ? (string) $body['profile'] : '';
        $server = isset($body['server']) ? (string) $body['server'] : '';

        if ($name === '' || $password === '') {
            return Response::json(['error' => 'name and password are required'], 422);
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $apiPassword);

            $words = [
                '=name=' . $name,
                '=password=' . $password,
                '=disabled=no',
            ];
            if ($profile !== '') {
                $words[] = '=profile=' . $profile;
            }
            if ($server !== '') {
                $words[] = '=server=' . $server;
            }

            $client->command('/ip/hotspot/user/add', $words);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }

            (new AuditRepository())->log($deviceId, 'hotspot.user.create.failed', [
                'name' => $name,
                'profile' => $profile ?: null,
                'server' => $server ?: null,
                'error' => $e->getMessage(),
            ]);

            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, 'hotspot.user.create.ok', [
            'name' => $name,
            'profile' => $profile ?: null,
            'server' => $server ?: null,
        ]);

        return Response::json(['ok' => true], 201);
    }

    public function removeUser(Request $request, array $params): Response
    {
        $deviceId = (int) ($params['id'] ?? 0);
        $name = (string) ($params['name'] ?? '');
        if ($deviceId <= 0 || $name === '') {
            return Response::json(['error' => 'Invalid device id or name'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $apiPassword);
            $rows = $client->rows('/ip/hotspot/user/print', ['?name=' . $name]);
            $id = $rows[0]['.id'] ?? null;
            if (!is_string($id) || $id === '') {
                $client->disconnect();
                return Response::json(['error' => 'Hotspot user not found'], 404);
            }
            $client->command('/ip/hotspot/user/remove', ['=numbers=' . $id]);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }
            (new AuditRepository())->log($deviceId, 'hotspot.user.remove.failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, 'hotspot.user.remove.ok', ['name' => $name]);
        return Response::json(['ok' => true]);
    }

    public function disableUser(Request $request, array $params): Response
    {
        return $this->setUserDisabled($request, $params, true);
    }

    public function enableUser(Request $request, array $params): Response
    {
        return $this->setUserDisabled($request, $params, false);
    }

    public function listActive(Request $request, array $params): Response
    {
        $deviceId = (int) ($params['id'] ?? 0);
        if ($deviceId <= 0) {
            return Response::json(['error' => 'Invalid device id'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $apiPassword);
            $rows = $client->rows('/ip/hotspot/active/print');
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }
            return Response::json(['error' => $e->getMessage()], 502);
        }

        return Response::json(['data' => $rows]);
    }

    public function removeActive(Request $request, array $params): Response
    {
        $deviceId = (int) ($params['id'] ?? 0);
        $activeId = (string) ($params['activeId'] ?? '');
        if ($deviceId <= 0 || $activeId === '') {
            return Response::json(['error' => 'Invalid device id or active id'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $apiPassword);
            $client->command('/ip/hotspot/active/remove', ['=numbers=' . $activeId]);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }
            (new AuditRepository())->log($deviceId, 'hotspot.active.remove.failed', [
                'active_id' => $activeId,
                'error' => $e->getMessage(),
            ]);
            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, 'hotspot.active.remove.ok', ['active_id' => $activeId]);
        return Response::json(['ok' => true]);
    }

    private function setUserDisabled(Request $request, array $params, bool $disabled): Response
    {
        $deviceId = (int) ($params['id'] ?? 0);
        $name = (string) ($params['name'] ?? '');
        if ($deviceId <= 0 || $name === '') {
            return Response::json(['error' => 'Invalid device id or name'], 422);
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            return Response::json(['error' => 'Device not found'], 404);
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);

        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
        );

        try {
            $client->connect();
            $client->login((string) $device['username'], $apiPassword);
            $rows = $client->rows('/ip/hotspot/user/print', ['?name=' . $name]);
            $id = $rows[0]['.id'] ?? null;
            if (!is_string($id) || $id === '') {
                $client->disconnect();
                return Response::json(['error' => 'Hotspot user not found'], 404);
            }
            $client->command('/ip/hotspot/user/set', [
                '=numbers=' . $id,
                '=disabled=' . ($disabled ? 'yes' : 'no'),
            ]);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }

            (new AuditRepository())->log($deviceId, $disabled ? 'hotspot.user.disable.failed' : 'hotspot.user.enable.failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, $disabled ? 'hotspot.user.disable.ok' : 'hotspot.user.enable.ok', [
            'name' => $name,
        ]);

        return Response::json(['ok' => true]);
    }
}
