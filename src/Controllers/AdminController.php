<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Mikrotik\RouterOSApiClient;
use App\Repositories\DeviceRepository;
use App\Repositories\ModuleConfigRepository;
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
            'flash' => $this->flash($request),
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
                'flash' => $this->flash($request),
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
        } elseif ($this->isConfigurablePage($page)) {
            $vars['devices'] = (new DeviceRepository())->all();
            $vars['config'] = (new ModuleConfigRepository())->get($page);

            if ($page === 'interfaces' && ((string) ($request->query['sync'] ?? '')) === '1') {
                $deviceId = (int) ($request->query['device_id'] ?? 0);
                if ($deviceId <= 0) {
                    $deviceId = (int) (($vars['config']['default_router_id'] ?? 0));
                }
                if ($deviceId > 0) {
                    try {
                        $vars['interfaces'] = $this->fetchInterfaces($deviceId);
                    } catch (\Throwable $e) {
                        $vars['interfaces_error'] = $e->getMessage();
                    }
                }
            }
        }

        $content = $this->view('admin/pages/' . $page, $vars);
        $html = $this->view('admin/layout', [
            'title' => $title,
            'active' => $page,
            'pages' => $this->pages(),
            'content' => $content,
            'includeDashboardScript' => false,
            'flash' => $this->flash($request),
        ]);

        return Response::html($html);
    }

    public function savePage(Request $request, array $params): Response
    {
        $page = isset($params['page']) ? (string) $params['page'] : '';
        if (!$this->isConfigurablePage($page)) {
            return Response::redirect('/admin/' . $page);
        }

        $form = $request->form();
        $action = isset($form['action']) ? (string) $form['action'] : 'save';

        try {
            if (!$this->validateCsrf($form)) {
                return Response::redirect('/admin/' . $page . '?error=' . rawurlencode('Invalid CSRF token'));
            }

            $config = $this->extractConfig($page, $form);
            (new ModuleConfigRepository())->set($page, $config);

            if ($page === 'adblocker' && $action === 'apply') {
                $this->applyAdblocker($config);
                return Response::redirect('/admin/adblocker?applied=1');
            }
            if ($page === 'pppoe' && $action === 'apply') {
                $this->applyPppoeDefaults($config);
                return Response::redirect('/admin/pppoe?applied=1');
            }

            return Response::redirect('/admin/' . $page . '?saved=1');
        } catch (\Throwable $e) {
            $msg = rawurlencode($e->getMessage());
            return Response::redirect('/admin/' . $page . '?error=' . $msg);
        }
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

    private function isConfigurablePage(string $page): bool
    {
        return in_array($page, [
            'interfaces',
            'pppoe',
            'vouchers',
            'qos',
            'adblocker',
            'subvendo',
            'maps',
            'portal',
            'chat',
            'hotspotsales',
            'pppoesales',
            'license',
        ], true);
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return '';
        }

        $token = $_SESSION['csrf_token'] ?? null;
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    private function validateCsrf(array $form): bool
    {
        $provided = isset($form['csrf_token']) ? (string) $form['csrf_token'] : '';
        $expected = $this->csrfToken();
        if ($provided === '' || $expected === '') {
            return false;
        }
        return hash_equals($expected, $provided);
    }

    /** @return array<string, mixed> */
    private function extractConfig(string $page, array $form): array
    {
        $config = [];

        if ($page === 'interfaces') {
            $config['default_router_id'] = (int) ($form['default_router_id'] ?? 0);
            $config['sync_interval_sec'] = (int) ($form['sync_interval_sec'] ?? 0);
        } elseif ($page === 'pppoe') {
            $config['device_id'] = (int) ($form['device_id'] ?? 0);
            $config['default_profile'] = (string) ($form['default_profile'] ?? '');
            $config['service_name'] = (string) ($form['service_name'] ?? '');
        } elseif ($page === 'vouchers') {
            $config['prefix'] = (string) ($form['prefix'] ?? '');
            $config['length'] = (int) ($form['length'] ?? 0);
        } elseif ($page === 'qos') {
            $config['device_id'] = (int) ($form['device_id'] ?? 0);
            $config['default_limit'] = (string) ($form['default_limit'] ?? '');
            $config['burst_limit'] = (string) ($form['burst_limit'] ?? '');
        } elseif ($page === 'adblocker') {
            $config['device_id'] = (int) ($form['device_id'] ?? 0);
            $config['mode'] = (string) ($form['mode'] ?? 'disabled');
            $config['redirect_ip'] = (string) ($form['redirect_ip'] ?? '0.0.0.0');
            $config['allowlist'] = (string) ($form['allowlist'] ?? '');
            $config['denylist'] = (string) ($form['denylist'] ?? '');
        } elseif ($page === 'subvendo') {
            $config['commission_percent'] = (string) ($form['commission_percent'] ?? '');
            $config['min_wallet_balance'] = (string) ($form['min_wallet_balance'] ?? '');
        } elseif ($page === 'maps') {
            $config['provider'] = (string) ($form['provider'] ?? '');
            $config['default_zoom'] = (string) ($form['default_zoom'] ?? '');
        } elseif ($page === 'portal') {
            $config['redirect_url'] = (string) ($form['redirect_url'] ?? '');
            $config['theme'] = (string) ($form['theme'] ?? '');
            $config['brand_name'] = (string) ($form['brand_name'] ?? '');
        } elseif ($page === 'chat') {
            $config['provider'] = (string) ($form['provider'] ?? '');
            $config['webhook_url'] = (string) ($form['webhook_url'] ?? '');
        } elseif ($page === 'hotspotsales' || $page === 'pppoesales') {
            $config['currency'] = (string) ($form['currency'] ?? '');
            $config['receipt_header'] = (string) ($form['receipt_header'] ?? '');
        } elseif ($page === 'license') {
            $config['license_key'] = (string) ($form['license_key'] ?? '');
            $config['company_name'] = (string) ($form['company_name'] ?? '');
        }

        return $config;
    }

    private function flash(Request $request): array
    {
        $saved = (string) ($request->query['saved'] ?? '');
        $applied = (string) ($request->query['applied'] ?? '');
        $error = (string) ($request->query['error'] ?? '');

        if ($error !== '') {
            return ['type' => 'error', 'message' => rawurldecode($error)];
        }
        if ($applied === '1') {
            return ['type' => 'success', 'message' => 'Applied'];
        }
        if ($saved === '1') {
            return ['type' => 'success', 'message' => 'Saved'];
        }

        return [];
    }

    /** @return array<int, array<string, string>> */
    private function fetchInterfaces(int $deviceId): array
    {
        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            throw new \RuntimeException('Device not found');
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);
        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
            5,
        );

        $client->connect();
        $client->login((string) $device['username'], $apiPassword);
        $rows = $client->rows('/interface/print');
        $client->disconnect();
        return $rows;
    }

    private function applyAdblocker(array $config): void
    {
        $deviceId = (int) ($config['device_id'] ?? 0);
        if ($deviceId <= 0) {
            throw new \RuntimeException('device_id is required');
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            throw new \RuntimeException('Device not found');
        }

        $mode = (string) ($config['mode'] ?? 'disabled');
        $redirectIp = (string) ($config['redirect_ip'] ?? '0.0.0.0');
        $allow = $this->parseDomains((string) ($config['allowlist'] ?? ''));
        $deny = $this->parseDomains((string) ($config['denylist'] ?? ''));

        $denySet = array_values(array_diff($deny, $allow));

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);
        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
            8,
        );

        $client->connect();
        $client->login((string) $device['username'], $apiPassword);

        $existing = $client->rows('/ip/dns/static/print', ['?comment=neofi-adblock']);
        foreach ($existing as $row) {
            $id = $row['.id'] ?? '';
            if (is_string($id) && $id !== '') {
                $client->command('/ip/dns/static/remove', ['=numbers=' . $id]);
            }
        }

        if ($mode === 'enabled') {
            foreach ($denySet as $domain) {
                $client->command('/ip/dns/static/add', [
                    '=name=' . $domain,
                    '=address=' . $redirectIp,
                    '=comment=neofi-adblock',
                ]);
            }
        }

        $client->disconnect();
    }

    private function applyPppoeDefaults(array $config): void
    {
        $deviceId = (int) ($config['device_id'] ?? 0);
        $profile = trim((string) ($config['default_profile'] ?? ''));
        if ($deviceId <= 0) {
            throw new \RuntimeException('device_id is required');
        }
        if ($profile === '') {
            throw new \RuntimeException('default_profile is required');
        }

        $device = (new DeviceRepository())->findWithSecret($deviceId);
        if ($device === null) {
            throw new \RuntimeException('Device not found');
        }

        $apiPassword = Crypto::decrypt((string) $device['password_ciphertext']);
        $client = new RouterOSApiClient(
            (string) $device['host'],
            (int) $device['api_port'],
            (bool) $device['use_ssl'],
            8,
        );

        $client->connect();
        $client->login((string) $device['username'], $apiPassword);

        $rows = $client->rows('/ppp/profile/print', ['?name=' . $profile]);
        if (count($rows) === 0) {
            $client->command('/ppp/profile/add', ['=name=' . $profile]);
        }

        $client->disconnect();
    }

    /** @return array<int, string> */
    private function parseDomains(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $parts = preg_split('/[\n,]+/', $text) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $d = strtolower(trim((string) $p));
            if ($d === '') {
                continue;
            }
            $d = preg_replace('/\s+/', '', $d) ?? $d;
            if ($d === '') {
                continue;
            }
            $out[] = $d;
        }

        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    private function view(string $view, array $vars): string
    {
        $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $vars['e'] = $e;
        $vars['csrfToken'] = $this->csrfToken();

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
