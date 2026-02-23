<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AddressModel;

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
            'title' => '預設地址 - ' . $this->getSiteName(),
            'addresses' => $addresses,
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
            $this->json(['error' => '無效的地址 ID'], 400);
            return;
        }
        $address = $this->addressModel->getAddressById($addressId, $userId);
        if (!$address) {
            $this->json(['error' => '地址不存在'], 404);
            return;
        }
        $this->json(['success' => true, 'address' => $address]);
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
            $this->json(['success' => true, 'message' => '地址新增成功', 'address_id' => $addressId]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('Address create: ' . $e->getMessage());
            $this->json(['error' => '新增地址失敗'], 500);
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
            $this->json(['error' => '無效的地址 ID'], 400);
            return;
        }
        unset($input['id']);
        try {
            $success = $this->addressModel->updateAddress($addressId, $userId, $input);
            if ($success) {
                $this->json(['success' => true, 'message' => '地址更新成功']);
            } else {
                $this->json(['error' => '地址不存在或無權限'], 404);
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('Address update: ' . $e->getMessage());
            $this->json(['error' => '更新地址失敗'], 500);
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
            $this->json(['error' => '無效的地址 ID'], 400);
            return;
        }
        try {
            $success = $this->addressModel->deleteAddress($addressId, $userId);
            if ($success) {
                $this->json(['success' => true, 'message' => '地址已刪除']);
            } else {
                $this->json(['error' => '地址不存在或無權限'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['error' => '刪除地址失敗'], 500);
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
            $this->json(['error' => '無效的地址 ID'], 400);
            return;
        }
        try {
            $success = $this->addressModel->setDefaultAddress($addressId, $userId);
            if ($success) {
                $this->json(['success' => true, 'message' => '預設地址已更新']);
            } else {
                $this->json(['error' => '地址不存在或無權限'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['error' => '設定預設地址失敗'], 500);
        }
    }
}
