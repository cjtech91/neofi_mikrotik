<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Mikrotik\RouterOSApiClient;
use App\Repositories\AuditRepository;
use App\Repositories\DeviceRepository;
use App\Security\Crypto;

final class PPPoEController
{
    public function listSecrets(Request $request, array $params): Response
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
            $rows = $client->rows('/ppp/secret/print');
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

    public function createSecret(Request $request, array $params): Response
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
        $service = isset($body['service']) ? (string) $body['service'] : 'pppoe';
        $localAddress = isset($body['local_address']) ? (string) $body['local_address'] : '';
        $remoteAddress = isset($body['remote_address']) ? (string) $body['remote_address'] : '';

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
                '=service=' . ($service !== '' ? $service : 'pppoe'),
                '=disabled=no',
            ];
            if ($profile !== '') {
                $words[] = '=profile=' . $profile;
            }
            if ($localAddress !== '') {
                $words[] = '=local-address=' . $localAddress;
            }
            if ($remoteAddress !== '') {
                $words[] = '=remote-address=' . $remoteAddress;
            }

            $client->command('/ppp/secret/add', $words);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }

            (new AuditRepository())->log($deviceId, 'pppoe.secret.create.failed', [
                'name' => $name,
                'service' => $service,
                'profile' => $profile ?: null,
                'error' => $e->getMessage(),
            ]);

            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, 'pppoe.secret.create.ok', [
            'name' => $name,
            'service' => $service,
            'profile' => $profile ?: null,
        ]);

        return Response::json(['ok' => true], 201);
    }

    public function removeSecret(Request $request, array $params): Response
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
            $rows = $client->rows('/ppp/secret/print', ['?name=' . $name]);
            $id = $rows[0]['.id'] ?? null;
            if (!is_string($id) || $id === '') {
                $client->disconnect();
                return Response::json(['error' => 'PPPoE secret not found'], 404);
            }
            $client->command('/ppp/secret/remove', ['=numbers=' . $id]);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }
            (new AuditRepository())->log($deviceId, 'pppoe.secret.remove.failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, 'pppoe.secret.remove.ok', ['name' => $name]);
        return Response::json(['ok' => true]);
    }

    public function disableSecret(Request $request, array $params): Response
    {
        return $this->setSecretDisabled($request, $params, true);
    }

    public function enableSecret(Request $request, array $params): Response
    {
        return $this->setSecretDisabled($request, $params, false);
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
            $rows = $client->rows('/ppp/active/print');
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

    public function disconnectActive(Request $request, array $params): Response
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
            $client->command('/ppp/active/remove', ['=numbers=' . $activeId]);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }
            (new AuditRepository())->log($deviceId, 'pppoe.active.disconnect.failed', [
                'active_id' => $activeId,
                'error' => $e->getMessage(),
            ]);
            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, 'pppoe.active.disconnect.ok', ['active_id' => $activeId]);
        return Response::json(['ok' => true]);
    }

    private function setSecretDisabled(Request $request, array $params, bool $disabled): Response
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
            $rows = $client->rows('/ppp/secret/print', ['?name=' . $name]);
            $id = $rows[0]['.id'] ?? null;
            if (!is_string($id) || $id === '') {
                $client->disconnect();
                return Response::json(['error' => 'PPPoE secret not found'], 404);
            }
            $client->command('/ppp/secret/set', [
                '=numbers=' . $id,
                '=disabled=' . ($disabled ? 'yes' : 'no'),
            ]);
            $client->disconnect();
        } catch (\Throwable $e) {
            try {
                $client->disconnect();
            } catch (\Throwable) {
            }

            (new AuditRepository())->log($deviceId, $disabled ? 'pppoe.secret.disable.failed' : 'pppoe.secret.enable.failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return Response::json(['error' => $e->getMessage()], 502);
        }

        (new AuditRepository())->log($deviceId, $disabled ? 'pppoe.secret.disable.ok' : 'pppoe.secret.enable.ok', [
            'name' => $name,
        ]);

        return Response::json(['ok' => true]);
    }
}
