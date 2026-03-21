<?php

namespace App\Controllers;

use App\Core\Config;
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
            'title' => $this->titleWithSite('address_default'),
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
