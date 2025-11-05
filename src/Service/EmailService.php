<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService extends BaseService
{
    private MailerInterface $mailer;

    private ParameterBagInterface $parameterBag;

    private bool $enabled;

    private string $senderName;

    private string $senderEmail;

    private string $returnEmail;

    private ?SlackService $slackService;

    public function __construct(
        ParameterBagInterface $parameterBag, 
        MailerInterface $mailer,
        ?SlackService $slackService = null
    ) {
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;
        $this->slackService = $slackService;

        $this->enabled = $this->parameterBag->get('app_email_enabled');
        $this->senderName = $this->parameterBag->get('app_email_sender_name');
        $this->senderEmail = $this->parameterBag->get('app_email_sender_email');
        $this->returnEmail = $this->parameterBag->get('app_email_return_email');
    }

    public function send(TemplatedEmail $message, Envelope $envelope = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $senderAddress = new Address($this->senderEmail, $this->senderName);
        $message->sender($senderAddress);
        $message->replyTo($senderAddress);
        $message->returnPath($this->returnEmail);
        $message->from($senderAddress);

        try {
            $this->mailer->send($message, $envelope);
            return true;
        } catch (TransportExceptionInterface $e) {
            // Send Slack notification on email failure
            if ($this->slackService !== null) {
                $recipients = $message->getTo();
                $recipientEmail = !empty($recipients) ? $recipients[0]->getAddress() : 'Unknown';
                
                $this->slackService->sendErrorNotification('Email Send Failed', [
                    'recipient' => $recipientEmail,
                    'subject' => $message->getSubject(),
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_type' => 'email_transport_exception'
                ]);
            }
            return false;
        } catch (\Exception $e) {
            // Send Slack notification on any other email error
            if ($this->slackService !== null) {
                $recipients = $message->getTo();
                $recipientEmail = !empty($recipients) ? $recipients[0]->getAddress() : 'Unknown';
                
                $this->slackService->sendErrorNotification('Email Send Failed - Unexpected Error', [
                    'recipient' => $recipientEmail,
                    'subject' => $message->getSubject(),
                    'error_message' => $e->getMessage(),
                    'error_type' => 'email_unexpected_error',
                    'trace' => $e->getTraceAsString()
                ]);
            }
            return false;
        }
    }
}
