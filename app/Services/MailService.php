<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailService
{
    private function buildMailer(): PHPMailer
    {
        $host = getenv('MAIL_HOST');
        $username = getenv('MAIL_USERNAME');
        $password = getenv('MAIL_PASSWORD');
        $fromAddress = getenv('MAIL_FROM_ADDRESS');

        if (empty($host) || empty($username) || empty($password) || empty($fromAddress)) {
            throw new \RuntimeException(
                \App\Core\Config::get('messages.mail.config_incomplete', 'MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS 未設定')
            );
        }

        $mail = new PHPMailer(true);

        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) (getenv('MAIL_PORT') ?: 587);

        $fromName = getenv('MAIL_FROM_NAME') ?: \App\Core\Config::get('messages.mail.from_name', '高達模型商城');

        $mail->setFrom($fromAddress, $fromName);
        $mail->addReplyTo($fromAddress, $fromName);
        $mail->isHTML(true);

        return $mail;
    }

    /**
     * Send password reset verification code email.
     *
     * @param string $toEmail Recipient email
     * @param string $toName  Recipient name
     * @param string $code    Verification code
     * @return bool
     */
    public function sendVerificationCode(string $toEmail, string $toName, string $code): bool
    {
        $ttlMinutes = \App\Core\Config::get('verification_code.ttl_minutes', 10);

        $mail = $this->buildMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = \App\Core\Config::get('messages.mail.subject_reset', '高達模型商城 - 密碼重置驗證碼');
        $mail->Body = sprintf(
            '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #333;">高達模型商城 密碼重置驗證碼</h2>
                <p>您好 %s，</p>
                <p>您正在申請重置密碼，您的驗證碼為：</p>
                <div style="text-align: center; margin: 30px 0;">
                    <h1 style="letter-spacing: 8px; color: #007bff; font-size: 32px; margin: 0;">%s</h1>
                </div>
                <p style="color: #666; font-size: 14px;">驗證碼 %d 分鐘內有效，請勿洩漏給他人。</p>
                <p style="color: #999; font-size: 12px; margin-top: 30px;">如非您本人操作，請忽略此郵件。</p>
            </div>',
            htmlspecialchars($toName),
            htmlspecialchars($code),
            $ttlMinutes
        );
        $mail->AltBody = "您好 {$toName}，您正在申請重置密碼，您的驗證碼為：{$code}，請在 {$ttlMinutes} 分鐘內使用。如非您本人操作，請忽略此郵件。";

        try {
            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Verification code mail failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send registration verification code email.
     *
     * @param string $toEmail Recipient email
     * @param string $toName  Recipient name
     * @param string $code    Verification code
     * @return bool
     */
    public function sendRegistrationCode(string $toEmail, string $toName, string $code): bool
    {
        $ttlMinutes = \App\Core\Config::get('verification_code.ttl_minutes', 10);

        $mail = $this->buildMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = \App\Core\Config::get('messages.mail.subject_register', '高達模型商城 - 註冊驗證碼');
        $mail->Body = sprintf(
            '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #333;">高達模型商城 註冊驗證碼</h2>
                <p>您好 %s，</p>
                <p>感謝您註冊高達模型商城！您的註冊驗證碼為：</p>
                <div style="text-align: center; margin: 30px 0;">
                    <h1 style="letter-spacing: 8px; color: #007bff; font-size: 32px; margin: 0;">%s</h1>
                </div>
                <p style="color: #666; font-size: 14px;">驗證碼 %d 分鐘內有效，請勿洩漏給他人。</p>
                <p style="color: #999; font-size: 12px; margin-top: 30px;">如非您本人操作，請忽略此郵件。</p>
            </div>',
            htmlspecialchars($toName),
            htmlspecialchars($code),
            $ttlMinutes
        );
        $mail->AltBody = "您好 {$toName}，感謝您註冊高達模型商城！您的註冊驗證碼為：{$code}，請在 {$ttlMinutes} 分鐘內使用。如非您本人操作，請忽略此郵件。";

        try {
            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Registration code mail failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send order confirmation email.
     *
     * @param string $toEmail         Recipient email
     * @param string $toName          Recipient name
     * @param string $orderNumber     Order number
     * @param float  $totalAmount     Order total
     * @param array  $items           Items [['name'=>'','qty'=>1,'price'=>0.0], ...]
     * @param string $shippingAddress Shipping address
     * @return bool
     */
    public function sendOrderConfirmation(
        string $toEmail,
        string $toName,
        string $orderNumber,
        float $totalAmount,
        array $items,
        string $shippingAddress
    ): bool {
        $mail = $this->buildMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = sprintf(\App\Core\Config::get('messages.mail.subject_order_confirm', '高達模型商城 - 訂單確認 %s'), $orderNumber);

        $rows = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['name'] ?? '');
            $qty = (int) ($item['qty'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $sub = $price * $qty;
            $rows .= '<tr><td>' . $name . '</td><td>' . $qty . '</td><td>' . htmlspecialchars(MoneyFormatter::format($price), ENT_QUOTES, 'UTF-8')
                . '</td><td>' . htmlspecialchars(MoneyFormatter::format($sub), ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }

        $totalFormatted = MoneyFormatter::format($totalAmount);

        $mail->Body = sprintf(
            '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #333;">訂單已確認</h2>
                <p>您好 %s，</p>
                <p>感謝您的訂購，訂單編號：<strong>%s</strong></p>
                <table style="width:100%%; border-collapse: collapse; margin: 20px 0;">
                    <thead><tr style="background:#f5f5f5;"><th style="padding:8px;text-align:left;">商品</th><th style="padding:8px;">數量</th><th style="padding:8px;">單價</th><th style="padding:8px;">小計</th></tr></thead>
                    <tbody>%s</tbody>
                </table>
                <p><strong>訂單總額：%s</strong></p>
                <p><strong>配送地址：</strong><br><span style="white-space:pre-line;">%s</span></p>
                <p style="color:#666;font-size:14px;">如有疑問請聯絡客服。</p>
            </div>',
            htmlspecialchars($toName),
            htmlspecialchars($orderNumber),
            $rows,
            htmlspecialchars($totalFormatted, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($shippingAddress)
        );
        $mail->AltBody = '訂單確認 ' . $orderNumber . '，總額 ' . $totalFormatted . '，配送：' . $shippingAddress;

        try {
            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Order confirmation mail failed: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
