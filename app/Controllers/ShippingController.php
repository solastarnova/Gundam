<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\CartModel;
use App\Services\LalamoveCheckoutService;
use RuntimeException;

class ShippingController extends Controller
{
    public function lalamoveQuote(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $msg = (string) Config::get('messages.common.method_not_allowed');
            $this->json(['success' => false, 'message' => $msg !== '' ? $msg : 'Method not allowed'], 405);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        $line = '';
        $coordinates = null;
        if (is_array($body) && isset($body['address_line'])) {
            $line = trim((string) $body['address_line']);
            if (isset($body['coordinates']) && is_array($body['coordinates'])) {
                $coordinates = [
                    'lat' => $body['coordinates']['lat'] ?? null,
                    'lng' => $body['coordinates']['lng'] ?? null,
                ];
            }
        }
        if ($coordinates === null && isset($_POST['coordinates']) && is_array($_POST['coordinates'])) {
            $coordinates = [
                'lat' => $_POST['coordinates']['lat'] ?? null,
                'lng' => $_POST['coordinates']['lng'] ?? null,
            ];
        }

        if ($line === '') {
            $msg = Config::get('messages.payment.shipping_required');
            $this->json(['success' => false, 'message' => $msg], 400);
            return;
        }

        $svc = LalamoveCheckoutService::fromConfigOrNull();
        if ($svc === null) {
            $msg = Config::get('messages.payment.lalamove_not_configured');
            $this->json(['success' => false, 'message' => $msg], 503);
            return;
        }

        if (mb_strlen($line) < 8) {
            $msg = Config::get('messages.payment.lalamove_address_too_short');
            $this->json(['success' => false, 'message' => $msg], 400);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $cartModel = new CartModel();
        $cartItems = $cartModel->getCartItems($userId);
        $totalQty = 0;
        foreach ($cartItems as $row) {
            $totalQty += (int) ($row['qty'] ?? 0);
        }
        if ($totalQty < 1) {
            $totalQty = 1;
        }

        try {
            $quote = $svc->quoteDelivery($line, $totalQty, $coordinates);
            $this->json([
                'success' => true,
                'shipping_fee' => $quote['fee'],
                'currency' => $quote['currency'],
                'quotation_id' => $quote['quotation_id'],
            ]);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('lalamoveQuote: ' . $e->getMessage());
            $msg = Config::get('messages.payment.lalamove_quote_failed');
            $this->json(['success' => false, 'message' => is_string($msg) ? $msg : 'Error'], 500);
        }
    }
}
