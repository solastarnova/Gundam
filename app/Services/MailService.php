<?php

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * SMTP mail: verification codes and order confirmation.
 */
class MailService
{
    private function getSiteName(): string
    {
        $name = trim((string) Config::get('site_name', '高達模型商城'));

        return $name !== '' ? $name : '高達模型商城';
    }

    private function buildVerificationCodeHtml(string $type, string $toName, string $code, int $ttlMinutes): string
    {
        $site = htmlspecialchars($this->getSiteName(), ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $codeEsc = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        if ($type === 'reset') {
            $title = $site . ' 密碼重置驗證碼';
            $lead = '您正在申請重置密碼，您的驗證碼為：';
        } else {
            $title = $site . ' 註冊驗證碼';
            $lead = '感謝您註冊 ' . $site . '！您的註冊驗證碼為：';
        }

        return sprintf(
            '<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #ffffff;">
                <h2 style="color: #333; font-size: 20px; margin: 0 0 16px 0;">%s</h2>
                <p style="color: #333; margin: 0 0 12px 0;">您好 %s，</p>
                <p style="color: #333; margin: 0 0 20px 0;">%s</p>
                <div style="text-align: center; margin: 28px 0;">
                    <div style="display: inline-block; padding: 20px 28px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px;">
                        <span style="letter-spacing: 10px; color: #0d6efd; font-size: 32px; font-weight: 700; line-height: 1.2;">%s</span>
                    </div>
                </div>
                <p style="color: #666; font-size: 14px; margin: 0 0 8px 0;">驗證碼 %d 分鐘內有效，請勿洩漏給他人。</p>
                <p style="color: #999; font-size: 12px; margin: 24px 0 0 0;">如非您本人操作，請忽略此郵件。</p>
            </div>',
            $title,
            $name,
            $lead,
            $codeEsc,
            $ttlMinutes
        );
    }

    private function buildMailer(): PHPMailer
    {
        $host = getenv('MAIL_HOST');
        $username = getenv('MAIL_USERNAME');
        $password = getenv('MAIL_PASSWORD');
        $fromAddress = getenv('MAIL_FROM_ADDRESS');

        if (empty($host) || empty($username) || empty($password) || empty($fromAddress)) {
            throw new \RuntimeException(
                'MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS 未設定'
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

        $fromName = getenv('MAIL_FROM_NAME') ?: '高達模型商城';

        $mail->setFrom($fromAddress, $fromName);
        $mail->addReplyTo($fromAddress, $fromName);
        $mail->isHTML(true);

        return $mail;
    }

    public function sendVerificationCode(string $toEmail, string $toName, string $code): bool
    {
        $ttlMinutes = (int) Config::get('verification_code.ttl_minutes', 10);
        $site = $this->getSiteName();

        $mail = $this->buildMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = sprintf('%s 驗證碼', $site);
        $mail->Body = $this->buildVerificationCodeHtml('reset', $toName, $code, $ttlMinutes);
        $mail->AltBody = "您好 {$toName}，您正在申請重置密碼，您的驗證碼為：{$code}，請在 {$ttlMinutes} 分鐘內使用。如非您本人操作，請忽略此郵件。";

        try {
            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Verification code mail failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendRegistrationCode(string $toEmail, string $toName, string $code): bool
    {
        $ttlMinutes = (int) Config::get('verification_code.ttl_minutes', 10);
        $site = $this->getSiteName();

        $mail = $this->buildMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = sprintf('%s 註冊驗證碼', $site);
        $mail->Body = $this->buildVerificationCodeHtml('register', $toName, $code, $ttlMinutes);
        $mail->AltBody = "您好 {$toName}，感謝您註冊 {$site}！您的註冊驗證碼為：{$code}，請在 {$ttlMinutes} 分鐘內使用。如非您本人操作，請忽略此郵件。";

        try {
            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('Registration code mail failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

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
        $mail->Subject = sprintf('高達模型商城 - 訂單確認 %s', $orderNumber);

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
