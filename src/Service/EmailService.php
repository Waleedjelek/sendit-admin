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

    public function __construct(ParameterBagInterface $parameterBag, MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;

        $this->enabled = $this->parameterBag->get('app_email_enabled');
        $this->senderName = $this->parameterBag->get('app_email_sender_name');
        $this->senderEmail = $this->parameterBag->get('app_email_sender_email');
        $this->returnEmail = $this->parameterBag->get('app_email_return_email');
    }

    public function send(TemplatedEmail $message, Envelope $envelope = null)
    {
        if (!$this->enabled) {
            return;
        }

        $senderAddress = new Address($this->senderEmail, $this->senderName);
        $message->sender($senderAddress);
        $message->replyTo($senderAddress);
        $message->returnPath($this->returnEmail);
        $message->from($senderAddress);

        try {
            $this->mailer->send($message, $envelope);
        } catch (TransportExceptionInterface $e) {
        }
    }
}
