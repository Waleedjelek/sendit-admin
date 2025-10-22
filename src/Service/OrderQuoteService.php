<?php

namespace App\Service;

use App\Entity\QuoteEntity;
use App\Entity\UserOrderEntity;
use Doctrine\ORM\EntityManagerInterface;

class OrderQuoteService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getTodayOrdersAndQuotes(): array
    {
        // Get the start and end of today's date
        $todayStart = (new \DateTime())->setTime(0, 0, 0);
        $todayEnd = (new \DateTime())->setTime(23, 59, 59);
        
        // Get the start of 30 days ago (to match the page's default date range)
        $thirtyDaysAgo = (new \DateTime())->modify('-29 days')->setTime(0, 0, 0);

        // Fetch orders with status 'Draft' or 'Ready' (unprocessed orders) from last 30 days
        $orderQuery = $this->entityManager->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $orderQuery->andWhere('o.status IN (:statuses)')
                   ->andWhere('o.createdDate >= :thirtyDaysAgo')
                   ->setParameter('statuses', ['Draft', 'Ready'])
                   ->setParameter('thirtyDaysAgo', $thirtyDaysAgo);
        $orderQuery->orderBy('o.createdDate', 'DESC');
        $newOrders = $orderQuery->getQuery()->getResult();

        // Fetch quotes created today
        $quoteQuery = $this->entityManager->getRepository(QuoteEntity::class)->createQueryBuilder('q');
        $quoteQuery->andWhere('q.createdDate BETWEEN :todayStart AND :todayEnd')
                   ->setParameter('todayStart', $todayStart->format('Y-m-d H:i:s'))
                   ->setParameter('todayEnd', $todayEnd->format('Y-m-d H:i:s'));
        $quoteQuery->orderBy('q.createdDate', 'DESC');
        $newQuotes = $quoteQuery->getQuery()->getResult();

        return [
            'newOrders' => $newOrders,
            'newQuotes' => $newQuotes,
        ];
    }
}
