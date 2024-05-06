<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\UserEntity;
use App\Entity\UserOrderEntity;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class OrderService extends BaseService
{
    private EntityManagerInterface $entityManager;

    private SerializerInterface $serializer;

    private PackageService $packageService;

    private Packages $assetPackage;

    private ParameterBagInterface $parameterBag;

    private string $orderPrefix;
    private TransactionService $transactionService;
    private EmailService $emailService;
    private RouterInterface $router;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        PackageService $packageService,
        Packages $assetPackage,
        ParameterBagInterface $parameterBag,
        TransactionService $transactionService,
        EmailService $emailService,
        RouterInterface $router
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->packageService = $packageService;
        $this->assetPackage = $assetPackage;
        $this->parameterBag = $parameterBag;

        $this->orderPrefix = $this->parameterBag->get('app_sendit_order_prefix');
        $this->transactionService = $transactionService;
        $this->emailService = $emailService;
        $this->router = $router;
    }

    public function getOrderById(string $orderId, UserEntity $userEntity): ?UserOrderEntity
    {
        $repo = $this->entityManager->getRepository(UserOrderEntity::class);

        return $repo->findOneBy([
            'id' => $orderId,
            'user' => $userEntity,
        ]);
    }

    public function getOrderByOrderId(string $orderId): ?UserOrderEntity
    {
        $repo = $this->entityManager->getRepository(UserOrderEntity::class);

        return $repo->findOneBy([
            'orderId' => $orderId,
        ]);
    }

    public function getUserOrders(UserEntity $userEntity): array
    {
        $repo = $this->entityManager->getRepository(UserOrderEntity::class);

        return $repo->findBy([
            'user' => $userEntity,
        ], [
            'createdDate' => 'DESC',
        ]);
    }

    public function generateOrderId(int $runIndex): string
    {
        $today = new \DateTime();

        return $this->orderPrefix.'-'.$today->format('Ym').'-'.str_pad($runIndex, 3, '0', STR_PAD_LEFT);
    }

    public function getRunIndex()
    {
        $repo = $this->entityManager->getRepository(UserOrderEntity::class);

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

    public function sendCustomerOrderEmail(UserOrderEntity $userOrderEntity)
    {
        $userEntity = $userOrderEntity->getUser();
        $transInfo = $this->transactionService->getTransactionArray($userOrderEntity->getPaidTransaction());

        $email = new TemplatedEmail();
        $email->addTo($userEntity->getEmail());
        $email->subject('Send it - Order #'.$userOrderEntity->getOrderId());
        $email->htmlTemplate('emails/user-order-info.html.twig');
        $email->context([
            'user' => $userEntity,
            'order' => $userOrderEntity,
            'transInfo' => $transInfo,
            'email_sent_to' => $userEntity->getEmail(),
        ]);
        $this->emailService->send($email);
    }

    public function sendAdminNotificationEmail(UserOrderEntity $userOrderEntity)
    {
        $notificationEmails = $this->parameterBag->get('app_sendit_order_notification_emails');
        if (empty($notificationEmails)) {
            return;
        }

        $adminWebsiteURL = $this->parameterBag->get('app_sendit_admin_website_url');
        $orderURL = $adminWebsiteURL.$this->generateUrl('app_order_show', ['id' => $userOrderEntity->getId()]);

        $notificationEmailList = explode(';', $notificationEmails);

        if (!empty($notificationEmailList)) {
            foreach ($notificationEmailList as $sendToEmail) {
                $email = new TemplatedEmail();
                $email->addTo($sendToEmail);
                $email->subject('New Order #'.$userOrderEntity->getOrderId());
                $email->htmlTemplate('emails/admin-order.html.twig');
                $email->context([
                    'orderURL' => $orderURL,
                    'orderId' => $userOrderEntity->getOrderId(),
                    'email_sent_to' => $sendToEmail,
                ]);
                $this->emailService->send($email);
            }
        }
    }

    public function getOrderArray(UserOrderEntity $userOrderEntity, string $assetURL): array
    {
        $selectedCompany = $userOrderEntity->getSelectedCompany();
        $selectedCompany->setImagePrefix($assetURL.'/uploads');
        $selectedCompanyArray = $this->serializer->toArray($selectedCompany);

        $packages = $userOrderEntity->getPackageInfo();
        if (!empty($packages)) {
            foreach ($packages as $packageIndex => $package) {
                $packageTypeEntity = $this->packageService->getByCode($package['type'].'sss', false);
                if (!is_null($packageTypeEntity)) {
                    $packageTypeEntity->setPackageImagePrefix($assetURL.'/uploads');
                    $packageTypeArray = $this->serializer->toArray($packageTypeEntity);
                    unset($packageTypeArray['description']);
                    unset($packageTypeArray['weight']);
                    unset($packageTypeArray['maxWeight']);
                    unset($packageTypeArray['length']);
                    unset($packageTypeArray['width']);
                    unset($packageTypeArray['height']);
                    $packages[$packageIndex]['package'] = $packageTypeArray;
                } else {
                    $packages[$packageIndex]['package'] = [
                        'type' => 'package',
                        'code' => $package['type'],
                        'name' => ucwords($package['type']),
                        'packageImage' => $assetURL.$this->assetPackage->getUrl('build/images/icons/package-type-box.svg'),
                        'iconImage' => $assetURL.$this->assetPackage->getUrl('build/images/icons/package-type-box-icon.svg'),
                    ];
                }
            }
        }

        $prices = $userOrderEntity->getPriceInfo();
        if (!empty($prices)) {
            foreach ($prices as $priceIndex => $price) {
                $prices[$priceIndex]['price'] = number_format($price['price'], 2);
            }
        }

        $data = [
            'id' => $userOrderEntity->getId(),
            'orderId' => $userOrderEntity->getOrderId(),
            'collectionDate' => $userOrderEntity->getCollectionDate()->format('Y-m-d'),
            'collectionTime' => $userOrderEntity->getCollectionTime(),
            'selectedCompany' => $selectedCompanyArray,
            'collectionAddress' => $userOrderEntity->getCollectionAddress(),
            'destinationAddress' => $userOrderEntity->getDestinationAddress(),
            'packages' => $packages,
            'prices' => $prices,
            'boeAmount' => number_format($userOrderEntity->getBoeAmount(), 2),
            'totalPrice' => number_format($userOrderEntity->getTotalPrice(), 2),
            'status' => $userOrderEntity->getStatus(),
            'paymentStatus' => $userOrderEntity->getPaymentStatus(),
            'createdDate' => $this->formatToTimezone($userOrderEntity->getCreatedDate()),
        ];
        $data['collectionAddress']['countryName'] = $userOrderEntity->getSourceCountry()->getName();
        $data['destinationAddress']['countryName'] = $userOrderEntity->getDestinationCountry()->getName();
        if ('Draft' != $userOrderEntity->getStatus()) {
            unset($data['collectionAddress']['id']);
            unset($data['destinationAddress']['id']);
        }

        return $data;
    }

    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->router->generate($route, $parameters, $referenceType);
    }
}
