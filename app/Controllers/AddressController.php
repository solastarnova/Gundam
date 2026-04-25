<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\AddressModel;

/** 處理地址管理頁面與相關 API。 */
class AddressController extends Controller
{
    private AddressModel $addressModel;

    public function __construct()
    {
        parent::__construct();
        $this->addressModel = new AddressModel();
    }

    public function index(): void
    {
        $user = $this->requireUser();
        $userId = (int) $user['id'];
        $addresses = $this->addressModel->getUserAddresses($userId);
        $this->render('account/addresses', [
            'title' => $this->titleWithSite('address_default'),
            'addresses' => $addresses,
            'mapClientConfig' => $this->getMapClientConfig(),
            'head_extra_css' => [],
            'foot_extra_js' => ['js/addresses.js'],
        ]);
    }

    public function list(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $addresses = $this->addressModel->getUserAddresses($userId);
        $this->json(['success' => true, 'addresses' => $addresses]);
    }

    public function get(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $addressId = (int) ($_GET['id'] ?? 0);
        if ($addressId <= 0) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.invalid_id'), 'message' => Config::get('messages.address.invalid_id')], 400);
            return;
        }
        $address = $this->addressModel->getAddressById($addressId, $userId);
        if (!$address) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.not_found'), 'message' => Config::get('messages.address.not_found')], 404);
            return;
        }
        $this->json(['success' => true, 'address' => $address]);
    }

    /**
     * 反向地址解析代理：由後端呼叫 Nominatim，避免瀏覽器跨域限制導致前端 fallback 失效。
     */
    public function reverseGeocode(): void
    {
        $this->setupJsonApi();

        $lat = trim((string) ($_GET['lat'] ?? ''));
        $lon = trim((string) ($_GET['lon'] ?? ''));
        if ($lat === '' || $lon === '' || !is_numeric($lat) || !is_numeric($lon)) {
            $this->json(['success' => false, 'error' => 'invalid_coordinates', 'message' => 'invalid_coordinates'], 400);
            return;
        }

        $nominatimUrl = (string) Config::get('map_client.nominatim_reverse_url', 'https://nominatim.openstreetmap.org/reverse');
        if ($nominatimUrl === '') {
            $nominatimUrl = 'https://nominatim.openstreetmap.org/reverse';
        }
        // 避免本端 endpoint 遞迴呼叫自己。
        if (str_starts_with($nominatimUrl, '/')) {
            $nominatimUrl = 'https://nominatim.openstreetmap.org/reverse';
        }

        $acceptLanguage = trim((string) ($_GET['accept-language'] ?? 'zh-HK'));
        if ($acceptLanguage === '') {
            $acceptLanguage = 'zh-HK';
        }

        $userAgent = (string) Config::get('lalamove.nominatim_user_agent', 'GundamShop/1.0 (reverse-geocode)');
        if ($userAgent === '') {
            $userAgent = 'GundamShop/1.0 (reverse-geocode)';
        }

        $query = http_build_query([
            'format' => 'jsonv2',
            'lat' => $lat,
            'lon' => $lon,
            'accept-language' => $acceptLanguage,
        ], '', '&', PHP_QUERY_RFC3986);

        $url = $nominatimUrl . (str_contains($nominatimUrl, '?') ? '&' : '?') . $query;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_TIMEOUT => 12,
        ]);
        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $httpCode !== 200) {
            $this->json(['success' => false, 'error' => 'nominatim_failed', 'message' => 'nominatim_failed'], 502);
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->json(['success' => false, 'error' => 'nominatim_invalid_response', 'message' => 'nominatim_invalid_response'], 502);
            return;
        }

        $this->json($decoded);
    }

    public function create(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        try {
            $addressId = $this->addressModel->createAddress($userId, $data);
            $this->json(['success' => true, 'message' => Config::get('messages.address.create_success'), 'address_id' => $addressId]);
        } catch (\InvalidArgumentException $e) {
            error_log('Address create validation: ' . $e->getMessage());
            $msg = Config::get('messages.address.validation_error');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        } catch (\Exception $e) {
            error_log('Address create: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => Config::get('messages.address.create_failed'), 'message' => Config::get('messages.address.create_failed')], 500);
        }
    }

    public function update(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $addressId = (int) ($input['id'] ?? 0);
        if ($addressId <= 0) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.invalid_id'), 'message' => Config::get('messages.address.invalid_id')], 400);
            return;
        }
        unset($input['id']);
        try {
            $success = $this->addressModel->updateAddress($addressId, $userId, $input);
            if ($success) {
                $this->json(['success' => true, 'message' => Config::get('messages.address.update_success')]);
            } else {
                $this->json(['success' => false, 'error' => Config::get('messages.address.not_found_or_forbidden'), 'message' => Config::get('messages.address.not_found_or_forbidden')], 404);
            }
        } catch (\InvalidArgumentException $e) {
            error_log('Address update validation: ' . $e->getMessage());
            $msg = Config::get('messages.address.validation_error');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        } catch (\Exception $e) {
            error_log('Address update: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => Config::get('messages.address.update_failed'), 'message' => Config::get('messages.address.update_failed')], 500);
        }
    }

    public function delete(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $addressId = (int) ($input['id'] ?? 0);
        if ($addressId <= 0) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.invalid_id'), 'message' => Config::get('messages.address.invalid_id')], 400);
            return;
        }
        try {
            $success = $this->addressModel->deleteAddress($addressId, $userId);
            if ($success) {
                $this->json(['success' => true, 'message' => Config::get('messages.address.delete_success')]);
            } else {
                $this->json(['success' => false, 'error' => Config::get('messages.address.not_found_or_forbidden'), 'message' => Config::get('messages.address.not_found_or_forbidden')], 404);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.delete_failed'), 'message' => Config::get('messages.address.delete_failed')], 500);
        }
    }

    public function setDefault(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $addressId = (int) ($input['id'] ?? 0);
        if ($addressId <= 0) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.invalid_id'), 'message' => Config::get('messages.address.invalid_id')], 400);
            return;
        }
        try {
            $success = $this->addressModel->setDefaultAddress($addressId, $userId);
            if ($success) {
                $this->json(['success' => true, 'message' => Config::get('messages.address.default_updated')]);
            } else {
                $this->json(['success' => false, 'error' => Config::get('messages.address.not_found_or_forbidden'), 'message' => Config::get('messages.address.not_found_or_forbidden')], 404);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => Config::get('messages.address.set_default_failed'), 'message' => Config::get('messages.address.set_default_failed')], 500);
        }
    }
}
