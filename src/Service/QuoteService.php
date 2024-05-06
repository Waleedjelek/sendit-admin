<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\QuoteEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class QuoteService extends BaseService
{
    private EntityManagerInterface $entityManager;

    private ParameterBagInterface $parameterBag;

    private string $quotePrefix;
    private EmailService $emailService;
    private RouterInterface $router;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        EmailService $emailService,
        RouterInterface $router
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

        $this->quotePrefix = $this->parameterBag->get('app_sendit_quote_prefix');
        $this->emailService = $emailService;
        $this->router = $router;
    }

    public function generateQuoteId(int $runIndex): string
    {
        $today = new \DateTime();

        return $this->quotePrefix.'-'.$today->format('Ym').'-'.str_pad($runIndex, 3, '0', STR_PAD_LEFT);
    }

    public function getRunIndex()
    {
        $repo = $this->entityManager->getRepository(QuoteEntity::class);

        $forDateTime = new \DateTime();

        $qb = $repo->createQueryBuilder('q');
        $qb->select('MAX(q.runIndex) AS max_count');
        $qb->andWhere(' q.createdDate LIKE :forDate ');
        $qb->setParameter('forDate', $forDateTime->format('Y-m').'%');
        $countResult = $qb->getQuery()->setFirstResult(0)->getScalarResult();
        $maxCount = $countResult[0]['max_count'];
        if (is_null($maxCount)) {
            $maxCount = 1;
        } else {
            $maxCount = $maxCount + 1;
        }

        return $maxCount;
    }

    public function sendCustomerEmail(QuoteEntity $quoteEntity)
    {
        $email = new TemplatedEmail();
        $email->addTo($quoteEntity->getContactEmail());
        $email->subject('Send it - Quote #'.$quoteEntity->getQuoteId());
        $email->htmlTemplate('emails/user-quote.html.twig');
        $email->context([
            'contactName' => $quoteEntity->getContactName(),
            'quoteId' => $quoteEntity->getQuoteId(),
            'email_sent_to' => $quoteEntity->getContactEmail(),
        ]);
        $this->emailService->send($email);
    }

    public function sendAdminNotificationEmail(QuoteEntity $quoteEntity)
    {
        $notificationEmails = $this->parameterBag->get('app_sendit_quote_notification_emails');
        if (empty($notificationEmails)) {
            return;
        }

        $adminWebsiteURL = $this->parameterBag->get('app_sendit_admin_website_url');
        $quoteURL = $adminWebsiteURL.$this->generateUrl('app_quote_show', ['id' => $quoteEntity->getId()]);

        $notificationEmailList = explode(';', $notificationEmails);

        if (!empty($notificationEmailList)) {
            foreach ($notificationEmailList as $sendToEmail) {
                $email = new TemplatedEmail();
                $email->addTo($sendToEmail);
                $email->subject('New Quote #'.$quoteEntity->getQuoteId());
                $email->htmlTemplate('emails/admin-quote.html.twig');
                $email->context([
                    'quoteURL' => $quoteURL,
                    'quoteId' => $quoteEntity->getQuoteId(),
                    'email_sent_to' => $sendToEmail,
                ]);
                $this->emailService->send($email);
            }
        }
    }

    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->router->generate($route, $parameters, $referenceType);
    }
}
