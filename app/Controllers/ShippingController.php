<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\CartModel;
use App\Services\CheckoutSnapshotService;
use App\Services\LalamoveCheckoutService;
use RuntimeException;

/** 提供結帳運費試算 API。 */
class ShippingController extends Controller
{
    /** 回傳指定地址的即時 Lalamove 報價。 */
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
            $norm = CheckoutSnapshotService::normalizeCheckoutAddress($line);
            $lat = null;
            $lng = null;
            if ($coordinates !== null && isset($coordinates['lat'], $coordinates['lng'])) {
                $lat = $coordinates['lat'] !== null && $coordinates['lat'] !== '' ? (string) $coordinates['lat'] : null;
                $lng = $coordinates['lng'] !== null && $coordinates['lng'] !== '' ? (string) $coordinates['lng'] : null;
            }
            $cartSig = CheckoutSnapshotService::cartSignature($cartModel, $cartItems);
            $checkoutToken = (new CheckoutSnapshotService())->issueFromQuote(
                $userId,
                (float) $quote['fee'],
                $norm,
                trim($line),
                $lat,
                $lng,
                $cartSig
            );
            $this->json([
                'success' => true,
                'shipping_fee' => $quote['fee'],
                'currency' => $quote['currency'],
                'quotation_id' => $quote['quotation_id'],
                'checkout_token' => $checkoutToken,
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
