<?php
/**
 * Telegram Notification Service
 *
 * Sends formatted customer feedback and alerts directly to a Telegram group/chat
 * using the Telegram Bot API via cURL.
 *
 * @package App\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Repositories\SettingsRepository;
use Throwable;

class TelegramService
{
    /**
     * Send a notification message to the configured Telegram chat/group.
     *
     * @param string $message HTML formatted message string
     * @return bool True on success, false on failure or if Telegram is disabled/unconfigured
     */
    public static function sendMessage(string $message): bool
    {
        try {
            $settings = new SettingsRepository();
            $enabled  = ($settings->get('telegram_enabled', '0')) === '1';
            $botToken = trim((string)$settings->get('telegram_bot_token', ''));
            $chatId   = trim((string)$settings->get('telegram_chat_id', ''));

            if (!$enabled || empty($botToken) || empty($chatId)) {
                return false;
            }

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $errNo    = curl_errno($ch);
            $errMsg   = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errNo !== 0) {
                Logger::error("TelegramService: cURL error ({$errNo}): {$errMsg}");
                return false;
            }

            if ($httpCode !== 200) {
                Logger::error("TelegramService: HTTP {$httpCode} response: {$response}");
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Logger::error("TelegramService: Unexpected error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a formatted Telegram alert for a newly submitted customer feedback.
     *
     * @param array $feedback Data array with keys: branch_name, table_number, rating_overall, etc.
     * @return bool
     */
    public static function sendFeedbackNotification(array $feedback): bool
    {
        $overall = (int)($feedback['rating_overall'] ?? 5);
        $stars = str_repeat('⭐', max(1, min(5, $overall)));

        $branch = !empty($feedback['branch_name']) ? htmlspecialchars((string)$feedback['branch_name'], ENT_QUOTES, 'UTF-8') : 'All Branches / General';
        $table  = !empty($feedback['table_number']) ? htmlspecialchars((string)$feedback['table_number'], ENT_QUOTES, 'UTF-8') : 'Not specified';
        $name   = !empty($feedback['customer_name']) ? htmlspecialchars((string)$feedback['customer_name'], ENT_QUOTES, 'UTF-8') : 'Anonymous';
        $phone  = !empty($feedback['customer_phone']) ? htmlspecialchars((string)$feedback['customer_phone'], ENT_QUOTES, 'UTF-8') : 'Not provided';
        $comment= !empty($feedback['comment']) ? htmlspecialchars((string)$feedback['comment'], ENT_QUOTES, 'UTF-8') : '<i>No comment left</i>';

        $coffee  = !empty($feedback['rating_coffee']) ? str_repeat('⭐', (int)$feedback['rating_coffee']) : '—';
        $food    = !empty($feedback['rating_food']) ? str_repeat('⭐', (int)$feedback['rating_food']) : '—';
        $service = !empty($feedback['rating_service']) ? str_repeat('⭐', (int)$feedback['rating_service']) : '—';

        $date = date('Y-m-d H:i:s');

        $msg = "☕ <b>NEW CUSTOMER FEEDBACK</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🌟 <b>Overall Rating:</b> {$stars} ({$overall}/5)\n";
        $msg .= "📍 <b>Branch:</b> {$branch}\n";
        $msg .= "🪑 <b>Table:</b> {$table}\n\n";

        $msg .= "📊 <b>Ratings Breakdown:</b>\n";
        $msg .= " • Coffee: {$coffee}\n";
        $msg .= " • Food: {$food}\n";
        $msg .= " • Service: {$service}\n\n";

        $msg .= "💬 <b>Comment:</b>\n";
        $msg .= "\"{$comment}\"\n\n";

        $msg .= "👤 <b>Customer:</b> {$name}\n";
        $msg .= "📞 <b>Phone:</b> {$phone}\n";
        $msg .= "⏰ <b>Submitted:</b> {$date}\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "<i>Trattoria Gusto Digital Menu</i>";

        return self::sendMessage($msg);
    }

    /**
     * Test connection to Telegram bot.
     *
     * @param string $botToken
     * @param string $chatId
     * @return array{success: bool, message: string}
     */
    public static function testConnection(string $botToken, string $chatId): array
    {
        $botToken = trim($botToken);
        $chatId   = trim($chatId);

        if (empty($botToken) || empty($chatId)) {
            return ['success' => false, 'message' => 'Bot Token and Chat ID cannot be empty.'];
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $testMsg = "☕ <b>Trattoria Gusto — Telegram Bot Connected!</b>\n\nThis is a test message confirming that your feedback notifications will be delivered here.";

        $data = [
            'chat_id'    => $chatId,
            'text'       => $testMsg,
            'parse_mode' => 'HTML',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $res = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errMsg = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo !== 0) {
            return ['success' => false, 'message' => "Connection failed: {$errMsg}"];
        }

        $json = json_decode((string)$res, true);
        if ($code === 200 && ($json['ok'] ?? false) === true) {
            return ['success' => true, 'message' => 'Test message sent successfully to your Telegram group!'];
        }

        $desc = $json['description'] ?? "HTTP error {$code}";
        return ['success' => false, 'message' => "Telegram API Error: {$desc}"];
    }
}

