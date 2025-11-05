<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SlackService extends BaseService
{
    private ParameterBagInterface $parameterBag;
    private bool $enabled;
    private ?string $webhookUrl;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        try {
            $this->enabled = $this->parameterBag->get('app_slack_enabled') ?? false;
            $this->webhookUrl = $this->parameterBag->get('app_slack_webhook_url') ?? null;
        } catch (\Exception $e) {
            $this->enabled = false;
            $this->webhookUrl = null;
        }
    }

    public function sendNotification(string $message, string $title = 'Sendit Admin Alert', string $priority = 'high'): void
    {
        if (!$this->enabled || empty($this->webhookUrl)) {
            return;
        }

        try {
            $client = new Client([
                'timeout' => 5.0,
            ]);

            $payload = [
                'text' => $title,
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message,
                        ],
                    ],
                ],
            ];

            // Add color indicator for priority
            if ($priority === 'high') {
                $payload['attachments'] = [
                    [
                        'color' => 'danger',
                    ],
                ];
            }

            $client->post($this->webhookUrl, [
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            // Silently fail to prevent notification errors from breaking the app
            error_log('Slack notification failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('Slack notification failed: ' . $e->getMessage());
        }
    }

    public function sendErrorNotification(string $errorMessage, array $context = []): void
    {
        $message = "🚨 *Error Alert*\n\n";
        $message .= "*Error:* {$errorMessage}\n";
        $message .= "*Time:* " . date('Y-m-d H:i:s') . "\n";

        if (!empty($context)) {
            $message .= "\n*Context:*\n";
            foreach ($context as $key => $value) {
                $formattedValue = is_array($value) || is_object($value) 
                    ? json_encode($value, JSON_PRETTY_PRINT) 
                    : (string) $value;
                $message .= "• *{$key}:* `{$formattedValue}`\n";
            }
        }

        $this->sendNotification($message, 'Sendit Admin - Error Alert', 'high');
    }
}

