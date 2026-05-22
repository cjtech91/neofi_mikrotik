<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Mikrotik\RouterOSApiClient;
use App\Repositories\DeviceRepository;
use App\Security\Crypto;

final class AdminController
{
    public function dashboard(Request $request, array $params): Response
    {
        $content = $this->view('admin/pages/dashboard', []);
        $html = $this->view('admin/layout', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'pages' => $this->pages(),
            'content' => $content,
            'includeDashboardScript' => true,
        ]);

        return Response::html($html);
    }

    public function page(Request $request, array $params): Response
    {
        $page = isset($params['page']) ? (string) $params['page'] : '';
        $meta = $this->pages();
        if (!isset($meta[$page])) {
            $content = $this->view('admin/pages/not-found', ['title' => 'Not Found']);
            $html = $this->view('admin/layout', [
                'title' => 'Not Found',
                'active' => '',
                'pages' => $this->pages(),
                'content' => $content,
                'includeDashboardScript' => false,
            ]);
            return Response::html($html, 404);
        }

        $title = $meta[$page]['label'];

        $vars = ['title' => $title];
        if ($page === 'devices') {
            $vars['devices'] = (new DeviceRepository())->all();
        } elseif ($page === 'logs') {
            $pdo = Database::instance()->pdo();
            $stmt = $pdo->query('
                SELECT a.id, a.action, a.created_at, COALESCE(d.name, \'-\') AS device_name
                FROM audits a
                LEFT JOIN devices d ON d.id = a.device_id
                ORDER BY a.id DESC
                LIMIT 50
            ');
            $vars['logs'] = $stmt->fetchAll();
        } elseif ($page === 'settings') {
            $vars['flags'] = [
                'APP_ENV' => (string) getenv('APP_ENV'),
                'APP_DEBUG' => (string) getenv('APP_DEBUG'),
                'APP_KEY' => getenv('APP_KEY') !== false && (string) getenv('APP_KEY') !== '' ? 'set' : 'not set',
                'API_KEY' => getenv('API_KEY') !== false && (string) getenv('API_KEY') !== '' ? 'set' : 'not set',
                'ADMIN_USER' => getenv('ADMIN_USER') !== false && (string) getenv('ADMIN_USER') !== '' ? 'set' : 'not set',
                'ADMIN_PASS' => getenv('ADMIN_PASS') !== false && (string) getenv('ADMIN_PASS') !== '' ? 'set' : 'not set',
            ];
        }

        $content = $this->view('admin/pages/' . $page, $vars);
        $html = $this->view('admin/layout', [
            'title' => $title,
            'active' => $page,
            'pages' => $this->pages(),
            'content' => $content,
            'includeDashboardScript' => false,
        ]);

        return Response::html($html);
    }

    public function overview(Request $request, array $params): Response
    {
        $host = [
            'app_env' => (string) getenv('APP_ENV'),
            'php_version' => PHP_VERSION,
            'server_time' => gmdate('c'),
            'db_ok' => false,
            'db_error' => null,
        ];

        try {
            $pdo = Database::instance()->pdo();
            $pdo->query('SELECT 1');
            $host['db_ok'] = true;
        } catch (\Throwable $e) {
            $host['db_ok'] = false;
            $host['db_error'] = $e->getMessage();
        }

        $devices = (new DeviceRepository())->all();
        $routers = [];
        foreach ($devices as $d) {
            $id = (int) ($d['id'] ?? 0);
            $name = (string) ($d['name'] ?? '');
            $hostAddr = (string) ($d['host'] ?? '');

            $row = [
                'id' => $id,
                'name' => $name,
                'host' => $hostAddr,
                'ok' => false,
                'message' => null,
                'identity' => null,
                'version' => null,
                'uptime' => null,
                'cpu_load' => null,
                'memory' => null,
            ];

            try {
                $device = (new DeviceRepository())->findWithSecret($id);
                if ($device === null) {
                    $row['message'] = 'Device not found';
                    $routers[] = $row;
                    continue;
                }

                $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);
                $client = new RouterOSApiClient(
                    (string) $device['host'],
                    (int) $device['api_port'],
                    (bool) $device['use_ssl'],
                    3,
                );

                $client->connect();
                $client->login((string) $device['username'], $apiPassword);

                $identity = $client->commandOrThrow('/system/identity/print');
                $resource = $client->commandOrThrow('/system/resource/print');

                $client->disconnect();

                $row['ok'] = true;
                $row['identity'] = $identity['name'] ?? null;
                $row['version'] = $resource['version'] ?? null;
                $row['uptime'] = $resource['uptime'] ?? null;
                $row['cpu_load'] = isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null;

                $free = $resource['free-memory'] ?? null;
                $total = $resource['total-memory'] ?? null;
                if (is_string($free) && is_string($total) && $free !== '' && $total !== '') {
                    $row['memory'] = $free . ' / ' . $total;
                }
            } catch (\Throwable $e) {
                $row['ok'] = false;
                $row['message'] = $e->getMessage();
            }

            $routers[] = $row;
        }

        return Response::json([
            'host' => $host,
            'routers' => $routers,
        ]);
    }

    private function pages(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'href' => '/admin'],
            'interfaces' => ['label' => 'Interfaces', 'href' => '/admin/interfaces'],
            'pppoe' => ['label' => 'PPPoE', 'href' => '/admin/pppoe'],
            'vouchers' => ['label' => 'Vouchers', 'href' => '/admin/vouchers'],
            'qos' => ['label' => 'QoS', 'href' => '/admin/qos'],
            'adblocker' => ['label' => 'Adblocker', 'href' => '/admin/adblocker'],
            'subvendo' => ['label' => 'Subvendo', 'href' => '/admin/subvendo'],
            'maps' => ['label' => 'MAps', 'href' => '/admin/maps'],
            'portal' => ['label' => 'Portal', 'href' => '/admin/portal'],
            'logs' => ['label' => 'Logs', 'href' => '/admin/logs'],
            'devices' => ['label' => 'Devices', 'href' => '/admin/devices'],
            'chat' => ['label' => 'Chat', 'href' => '/admin/chat'],
            'hotspotsales' => ['label' => 'Hotspot Sales', 'href' => '/admin/hotspotsales'],
            'pppoesales' => ['label' => 'PPPOE Sales', 'href' => '/admin/pppoesales'],
            'license' => ['label' => 'License', 'href' => '/admin/license'],
            'settings' => ['label' => 'Settings', 'href' => '/admin/settings'],
        ];
    }

    private function view(string $view, array $vars): string
    {
        $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $vars['e'] = $e;

        $path = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($path)) {
            return '';
        }

        extract($vars, EXTR_SKIP);

        ob_start();
        require $path;
        $out = ob_get_clean();
        return is_string($out) ? $out : '';
    }
}
