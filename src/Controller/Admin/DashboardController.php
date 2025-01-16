<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\QuoteEntity;
use App\Entity\UserOrderEntity;
use App\Entity\CompanyEntity;
use App\Entity\UserTransactionEntity;
use App\Service\OrderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AdminController
{
    /**
     * @Route("/", name="app_home")
     * @Route("/admin/", name="app_admin_home")
     */
    public function home(
        ParameterBagInterface $parameterBag
    ): Response {
        if ($this->isGranted('ROLE_EDITOR')) {
            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_FOUND);
        }
        $redirectURL = $parameterBag->get('app_sendit_marketing_website_url');

        return $this->redirect($redirectURL, Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/admin/dashboard", name="app_dashboard")
     * @IsGranted("ROLE_EDITOR")
     */

    // public function index(Request $request): Response
    // {
    //     $fromDate = $request->query->get('fromDate');
    //     $toDate = $request->query->get('toDate');

    //     $ordersQuery = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');

    //     if ($fromDate && $toDate) {
    //         $ordersQuery->where('o.createdDate BETWEEN :fromDate AND :toDate')
    //             ->setParameter('fromDate', new \DateTime($fromDate))
    //             ->setParameter('toDate', new \DateTime($toDate));
    //     }

    //     $ordersQuery->setFirstResult(0)
    //         ->setMaxResults(5)
    //         ->orderBy('o.createdDate', 'DESC');

    //     $orders = $ordersQuery->getQuery()->getResult();

    //     $transactionsQuery = $this->em()->getRepository(UserTransactionEntity::class)->createQueryBuilder('t');

    //     if ($fromDate && $toDate) {
    //         $transactionsQuery->where('t.createdDate BETWEEN :fromDate AND :toDate')
    //             ->setParameter('fromDate', new \DateTime($fromDate))
    //             ->setParameter('toDate', new \DateTime($toDate));
    //     }

    //     $transactionsQuery->setFirstResult(0)
    //         ->setMaxResults(5)
    //         ->orderBy('t.createdDate', 'DESC');

    //     $transactions = $transactionsQuery->getQuery()->getResult();

    //     $quotesQuery = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');

    //     if ($fromDate && $toDate) {
    //         $quotesQuery->where('q.createdDate BETWEEN :fromDate AND :toDate')
    //             ->setParameter('fromDate', new \DateTime($fromDate))
    //             ->setParameter('toDate', new \DateTime($toDate));
    //     }

    //     $quotesQuery->setFirstResult(0)
    //         ->setMaxResults(5)
    //         ->orderBy('q.createdDate', 'DESC');

    //     $quotes = $quotesQuery->getQuery()->getResult();

    //     return $this->render('controller/dashboard/index.html.twig', [
    //         'orders' => $orders,
    //         'transactions' => $transactions,
    //         'quotes' => $quotes,
    //         'fromDate' => $fromDate,
    //         'toDate' => $toDate
    //     ]);
    // }
    public function index(Request $request): Response
    {
        $fromDate = $request->query->get('fromDate');
        $toDate = $request->query->get('toDate');

        $ordersQuery = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');

        if ($fromDate && $toDate) {
            $ordersQuery->where('o.createdDate BETWEEN :fromDate AND :toDate')
                ->setParameter('fromDate', new \DateTime($fromDate))
                ->setParameter('toDate', new \DateTime($toDate));
        }
        $ordersQuery->setFirstResult(0)
            ->setMaxResults(5)
            ->orderBy('o.createdDate', 'DESC');

        $orders = $ordersQuery->getQuery()->getResult();

        $transactionsQuery = $this->em()->getRepository(UserTransactionEntity::class)->createQueryBuilder('t');

        if ($fromDate && $toDate) {
            $transactionsQuery->where('t.createdDate BETWEEN :fromDate AND :toDate')
                ->setParameter('fromDate', new \DateTime($fromDate))
                ->setParameter('toDate', new \DateTime($toDate));
        }

        $transactionsQuery->setFirstResult(0)
            ->setMaxResults(5)
            ->orderBy('t.createdDate', 'DESC');

        $transactions = $transactionsQuery->getQuery()->getResult();

        $quotesQuery = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');

        if ($fromDate && $toDate) {
            $quotesQuery->where('q.createdDate BETWEEN :fromDate AND :toDate')
                ->setParameter('fromDate', new \DateTime($fromDate))
                ->setParameter('toDate', new \DateTime($toDate));
        }

        $quotesQuery->setFirstResult(0)
            ->setMaxResults(5)
            ->orderBy('q.createdDate', 'DESC');

        $quotes = $quotesQuery->getQuery()->getResult();

        $qbCount = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');
        $qbCount->select('COUNT(q) AS countNewQuotes')
            ->where('q.status = :status')
            ->setParameter('status', 'New');
        $countNewQuotes = $qbCount->getQuery()->getSingleScalarResult();

        $qbCountOrders = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $qbCountOrders->select('
            SUM(CASE WHEN o.status = :draft THEN 1 ELSE 0 END) AS countDraftOrders,
            SUM(CASE WHEN o.status = :new THEN 1 ELSE 0 END) AS countNewOrders,
            SUM(CASE WHEN o.status = :pending THEN 1 ELSE 0 END) AS countPendingOrders,
            SUM(CASE WHEN o.status = :process THEN 1 ELSE 0 END) AS countProcessOrders,
            SUM(CASE WHEN o.status = :cancel THEN 1 ELSE 0 END) AS countCancelledOrders
        ')
            ->setParameter('draft', 'Draft')
            ->setParameter('new', 'Collected')
            ->setParameter('pending', 'Pending')
            ->setParameter('process', 'Processing')
            ->setParameter('cancel', 'Cancelled');

        $result = $qbCountOrders->getQuery()->getSingleResult();

        $countDraftOrders = $result['countDraftOrders'];
        $countNewOrders = $result['countNewOrders'];
        $countPendingOrders = $result['countPendingOrders'];
        $countProcessOrders = $result['countProcessOrders'];
        $countCancelledOrders = $result['countCancelledOrders'];

        $qbCountOrdersPrice = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');

        $qbCountOrdersPrice->select('
            SUM(CASE WHEN o.createdDate >= :sevenDaysAgo THEN o.totalPrice ELSE 0 END) AS totalPriceLastSevenDays,
            SUM(CASE WHEN o.createdDate >= :thirtyDaysAgo THEN o.totalPrice ELSE 0 END) AS totalPriceLastThirtyDays,
            SUM(CASE WHEN o.createdDate >= :lastYear THEN o.totalPrice ELSE 0 END) AS totalPriceLastYear
        ')
            ->setParameter('sevenDaysAgo', new \DateTime('-7 days'))
            ->setParameter('thirtyDaysAgo', new \DateTime('-30 days'))
            ->setParameter('lastYear', new \DateTime('-365 days'));

        $result = $qbCountOrdersPrice->getQuery()->getSingleResult();

        $totalPriceLastSevenDays = $result['totalPriceLastSevenDays'];
        $totalPriceLastThirtyDays = $result['totalPriceLastThirtyDays'];
        $totalPriceLastYear = $result['totalPriceLastYear'];

        $totalPriceLastSevenDaysFormatted = number_format($totalPriceLastSevenDays, 2, '.', ',');
        $totalPriceLastThirtyDaysFormatted = number_format($totalPriceLastThirtyDays, 2, '.', ',');
        $totalPriceLastYearFormatted = number_format($totalPriceLastYear, 2, '.', ',');
        $activeCompaniesCount = $this->em()->getRepository(CompanyEntity::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();


        return $this->render('controller/dashboard/index.html.twig', [
            'orders' => $orders,
            'transactions' => $transactions,
            'quotes' => $quotes,
            'controller_name' => 'HomeController',
            'countNewQuotes' => $countNewQuotes,
            'countProcessOrders' => $countProcessOrders,
            'countDraftOrders' => $countDraftOrders,
            'countNewOrders' => $countNewOrders,
            'countPendingOrders' => $countPendingOrders,
            'countCancelledOrders' => $countCancelledOrders,
            'totalPriceLastSevenDays' => $totalPriceLastSevenDaysFormatted,
            'totalPriceLastThirtyDays' => $totalPriceLastThirtyDaysFormatted,
            'totalPriceLastYear' => $totalPriceLastYearFormatted,
            'activeCompaniesCount' => $activeCompaniesCount,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ]);
    }

    //     public function index(): Response
    // {
    //     $qb = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
    //     $qb->setFirstResult(0);
    //     $qb->setMaxResults(5);
    //     $qb->add('orderBy', ' o.createdDate DESC ');
    //     $orders = $qb->getQuery()->getResult();

    //     $qb = $this->em()->getRepository(UserTransactionEntity::class)->createQueryBuilder('t');
    //     $qb->setFirstResult(0);
    //     $qb->setMaxResults(5);
    //     $qb->add('orderBy', ' t.createdDate DESC ');
    //     $transactions = $qb->getQuery()->getResult();

    //     $qb = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');
    //     $qb->setFirstResult(0);
    //     $qb->setMaxResults(5);
    //     $qb->add('orderBy', ' q.createdDate DESC ');
    //     $quotes = $qb->getQuery()->getResult();

    //     $qbCount = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');
    //     $qbCount->select('COUNT(q) AS countNewQuotes')
    //             ->where('q.status = :status')
    //             ->setParameter('status', 'New');
    //     $countNewQuotes = $qbCount->getQuery()->getSingleScalarResult();

    //     $qbCountOrders = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
    //     $qbCountOrders->select('
    //         SUM(CASE WHEN o.status = :draft THEN 1 ELSE 0 END) AS countDraftOrders,
    //         SUM(CASE WHEN o.status = :new THEN 1 ELSE 0 END) AS countNewOrders,
    //         SUM(CASE WHEN o.status = :pending THEN 1 ELSE 0 END) AS countPendingOrders,
    //         SUM(CASE WHEN o.status = :process THEN 1 ELSE 0 END) AS countProcessOrders,
    //         SUM(CASE WHEN o.status = :cancel THEN 1 ELSE 0 END) AS countCancelledOrders
    //     ')
    //     ->setParameter('draft', 'Draft')
    //     ->setParameter('new', 'Collected')
    //     ->setParameter('pending', 'Pending')
    //     ->setParameter('process', 'Processing')
    //     ->setParameter('cancel', 'Cancelled');

    //     $result = $qbCountOrders->getQuery()->getSingleResult();  

    //     $countDraftOrders = $result['countDraftOrders'];
    //     $countNewOrders = $result['countNewOrders'];
    //     $countPendingOrders = $result['countPendingOrders'];
    //     $countProcessOrders = $result['countProcessOrders'];
    //     $countCancelledOrders = $result['countCancelledOrders'];

    //     $qbCountOrdersPrice = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');

    //     $qbCountOrdersPrice->select('
    //         SUM(CASE WHEN o.createdDate >= :sevenDaysAgo THEN o.totalPrice ELSE 0 END) AS totalPriceLastSevenDays,
    //         SUM(CASE WHEN o.createdDate >= :thirtyDaysAgo THEN o.totalPrice ELSE 0 END) AS totalPriceLastThirtyDays,
    //         SUM(CASE WHEN o.createdDate >= :lastYear THEN o.totalPrice ELSE 0 END) AS totalPriceLastYear
    //     ')
    //     ->setParameter('sevenDaysAgo', new \DateTime('-7 days')) 
    //     ->setParameter('thirtyDaysAgo', new \DateTime('-30 days')) 
    //     ->setParameter('lastYear', new \DateTime('-365 days')); 

    //     $result = $qbCountOrdersPrice->getQuery()->getSingleResult(); 

    //     $totalPriceLastSevenDays = $result['totalPriceLastSevenDays']; 
    //     $totalPriceLastThirtyDays = $result['totalPriceLastThirtyDays']; 
    //     $totalPriceLastYear = $result['totalPriceLastYear']; 

    //     $totalPriceLastSevenDaysFormatted = number_format($totalPriceLastSevenDays, 2, '.', ',');
    //     $totalPriceLastThirtyDaysFormatted = number_format($totalPriceLastThirtyDays, 2, '.', ',');
    //     $totalPriceLastYearFormatted = number_format($totalPriceLastYear, 2, '.', ',');

    //     return $this->render('controller/dashboard/index.html.twig', [
    //         'orders' => $orders,
    //         'transactions' => $transactions,
    //         'quotes' => $quotes,
    //         'controller_name' => 'HomeController',
    //         'countNewQuotes' => $countNewQuotes,
    //         'countProcessOrders' => $countProcessOrders,
    //         'countDraftOrders' => $countDraftOrders,
    //         'countNewOrders' => $countNewOrders,
    //         'countPendingOrders' => $countPendingOrders,
    //         'countCancelledOrders' => $countCancelledOrders,
    //         'totalPriceLastSevenDays' => $totalPriceLastSevenDaysFormatted,
    //         'totalPriceLastThirtyDays' => $totalPriceLastThirtyDaysFormatted,
    //         'totalPriceLastYear' => $totalPriceLastYearFormatted
    //     ]);
    // }

    /**
     * @Route("/admin/test-email/order/{id}", name="app_test_email_order")
     */
    public function sample(UserOrderEntity $userOrderEntity, OrderService $orderService): Response
    {
        $orderService->sendCustomerOrderEmail($userOrderEntity);

        exit('email');
    }
}


