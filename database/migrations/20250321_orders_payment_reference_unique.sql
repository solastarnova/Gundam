-- 冪等建單：同一使用者 + 支付渠道 + 支付參考（Stripe payment_intent_id / PayPal order id / wallet:訂單編號）僅允許一筆訂單
-- 執行：mysql -u ... mydb < database/migrations/20250321_orders_payment_reference_unique.sql

ALTER TABLE `orders`
  MODIFY `payment_reference` varchar(255) DEFAULT NULL COMMENT 'Stripe PI / PayPal id / wallet:訂單編號';

-- 若已有重複 (user_id, payment_provider, payment_reference) 需先清理後再執行
ALTER TABLE `orders`
  ADD UNIQUE KEY `uq_orders_user_provider_payment_ref` (`user_id`, `payment_provider`, `payment_reference`);
