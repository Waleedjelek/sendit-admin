<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Service\OrderQuoteService;
use App\Entity\QuoteEntity;
use App\Entity\UserEntity;
use App\Entity\UserOrderEntity;
use App\Entity\UserOrderNoteEntity;
use App\Form\OrderAddNoteType;
use App\Form\OrderAddTrackingType;
use App\Repository\CountryEntityRepository;
use App\Service\TrackService;
use App\Service\TransactionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/orders")
 * @IsGranted("ROLE_EDITOR")
 */
class OrderController extends AdminController
{
    private $orderQuoteService;

    public function __construct(OrderQuoteService $orderQuoteService)
    {
        $this->orderQuoteService = $orderQuoteService;
    }

    /**
     * @Route("/", name="app_order_index",  methods={"GET","POST"})
     */
    public function index(
        Request $request,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $orderStatus = [
            'Draft',
            'Ready',
            'Processing',
            'Collected',
            'Shipped',
            'Cancelled',
        ];

       // Handle GET request to render the page
        if ($request->isMethod('GET')) {
            $data = $this->orderQuoteService->getTodayOrdersAndQuotes();
            return $this->render('controller/order/index.html.twig', [
                'newOrders' => $data['newOrders'],
                'newQuotes' => $data['newQuotes'],
                'orderStatus' => $orderStatus,
            ]);
        }
     

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        if ($length < 0) {
            $length = 10;
        }

        $filterStatus = $request->get('filter_status', '');
        $filterStartDate = $request->get('filter_start_date', '');
        $filterEndDate = $request->get('filter_end_date', '');

        $qb = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $qb->leftJoin('o.selectedCompany', 'c');
        $qb->leftJoin('o.sourceCountry', 'm');

        $search = $request->get('search');
        $searchString = null;
        if (null != $search && isset($search['value']) && !empty($search['value'])) {
            $searchString = $search['value'];
        }
        if (!empty($searchString)) {
            $qb->andWhere(' ( o.orderId LIKE :query1 OR  o.status LIKE :query1 OR  o.paymentStatus LIKE :query1 OR  o.type LIKE :query1 OR  o.createdDate LIKE :query1 OR  c.name LIKE :query1 OR  m.code LIKE :query1) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }
        if (!empty($filterStatus)) {
            $qb->andWhere(' ( o.status LIKE :status ) ');
            $qb->setParameter('status', '%'.$filterStatus.'%');
        } else {
            $qb->andWhere(" ( o.status != 'Draft' ) ");
        }
        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            if ($filterStartDate != $filterEndDate) {
                $qb->andWhere($qb->expr()->between('o.createdDate', ':startDate', ':endDate'));
                $qb->setParameter('startDate', $filterStartDate.' 00:00:00');
                $qb->setParameter('endDate', $filterEndDate.' 23:59:59');
            } else {
                $qb->andWhere(' ( o.createdDate LIKE :onlyDate ) ');
                $qb->setParameter('onlyDate', '%'.$filterStartDate.'%');
            }
        }

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        $qb->add('orderBy', ' o.createdDate DESC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(o) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $userOrderEntity UserOrderEntity
             */
            foreach ($result as $userOrderEntity) {
                $ca = [];
                $ca['DT_RowId'] = $userOrderEntity->getId();
                $ca['orderId'] = $userOrderEntity->getOrderId();
                $ca['company'] = $userOrderEntity->getSelectedCompany()->getName();
                $ca['method'] = ucwords($userOrderEntity->getMethod());
                $ca['from'] = $userOrderEntity->getSourceCountry()->getCode();
                $ca['to'] = $userOrderEntity->getDestinationCountry()->getCode();
                $ca['weight'] = number_format($userOrderEntity->getFinalWeight(), 3);
                $ca['price'] = number_format($userOrderEntity->getTotalPrice(), 2);
                $ca['user'] = [
                    'firstName' => $userOrderEntity->getUser()->getFirstName(),
                    'lastName' => $userOrderEntity->getUser()->getLastName(),
                    'email' => $userOrderEntity->getUser()->getEmail(),
                    'mobile' => $userOrderEntity->getUser()->getMobileNumber(),
                ];
                $ca['status'] = $userOrderEntity->getStatus();
                $ca['type'] = $userOrderEntity->getType();
                $ca['coupon'] = $userOrderEntity->getCouponCode();
                $ca['paymentStatus'] = $userOrderEntity->getPaymentStatus();
                $ca['createdDate'] = $this->formatToTimezone($userOrderEntity->getCreatedDate());
                $ca['action'] = [
                    'details' => $this->generateUrl('app_order_show', ['id' => $userOrderEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }
    /**
     * @Route("/new-order", name="app_new_order_index",  methods={"GET","POST"})
     */
    public function newOrder(
        Request $request,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $orderStatus = [
            'Draft',
            'Ready',
            'Processing',
            'Collected',
            'Shipped',
            'Cancelled',
        ];

       // Handle GET request to render the page
        if ($request->isMethod('GET')) {
            $data = $this->orderQuoteService->getTodayOrdersAndQuotes();
            return $this->render('controller/order/new-order.html.twig', [
                'newOrders' => $data['newOrders'],
                'newQuotes' => $data['newQuotes'],
                'orderStatus' => $orderStatus,
            ]);
        }
     

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        if ($length < 0) {
            $length = 10;
        }

        $filterStatus = $request->get('filter_status', '');
        $filterStartDate = $request->get('filter_start_date', '');
        $filterEndDate = $request->get('filter_end_date', '');

        $qb = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');
        $qb->leftJoin('o.selectedCompany', 'c');
        $qb->leftJoin('o.sourceCountry', 'm');

        $search = $request->get('search');
        $searchString = null;
        if (null != $search && isset($search['value']) && !empty($search['value'])) {
            $searchString = $search['value'];
        }
        if (!empty($searchString)) {
            $qb->andWhere(' ( o.orderId LIKE :query1 OR  o.status LIKE :query1 OR  o.paymentStatus LIKE :query1 OR  o.type LIKE :query1 OR  o.createdDate LIKE :query1 OR  c.name LIKE :query1 OR  m.code LIKE :query1) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }
        if (!empty($filterStatus)) {
            $qb->andWhere(' ( o.status LIKE :status ) ');
            $qb->setParameter('status', '%'.$filterStatus.'%');
        } else {
            $qb->andWhere(" ( o.status IN ('Draft', 'Ready') ) ");
        }
        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            if ($filterStartDate != $filterEndDate) {
                $qb->andWhere($qb->expr()->between('o.createdDate', ':startDate', ':endDate'));
                $qb->setParameter('startDate', $filterStartDate.' 00:00:00');
                $qb->setParameter('endDate', $filterEndDate.' 23:59:59');
            } else {
                $qb->andWhere(' ( o.createdDate LIKE :onlyDate ) ');
                $qb->setParameter('onlyDate', '%'.$filterStartDate.'%');
            }
        }

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        $qb->add('orderBy', ' o.createdDate DESC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(o) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $userOrderEntity UserOrderEntity
             */
            foreach ($result as $userOrderEntity) {
                $ca = [];
                $ca['DT_RowId'] = $userOrderEntity->getId();
                $ca['orderId'] = $userOrderEntity->getOrderId();
                $ca['company'] = $userOrderEntity->getSelectedCompany()->getName();
                $ca['method'] = ucwords($userOrderEntity->getMethod());
                $ca['from'] = $userOrderEntity->getSourceCountry()->getCode();
                $ca['to'] = $userOrderEntity->getDestinationCountry()->getCode();
                $ca['weight'] = number_format($userOrderEntity->getFinalWeight(), 3);
                $ca['price'] = number_format($userOrderEntity->getTotalPrice(), 2);
                $ca['user'] = [
                    'firstName' => $userOrderEntity->getUser()->getFirstName(),
                    'lastName' => $userOrderEntity->getUser()->getLastName(),
                    'email' => $userOrderEntity->getUser()->getEmail(),
                    'mobile' => $userOrderEntity->getUser()->getMobileNumber(),
                ];
                $ca['status'] = $userOrderEntity->getStatus();
                $ca['type'] = $userOrderEntity->getType();
                $ca['coupon'] = $userOrderEntity->getCouponCode();
                $ca['paymentStatus'] = $userOrderEntity->getPaymentStatus();
                $ca['createdDate'] = $this->formatToTimezone($userOrderEntity->getCreatedDate());
                $ca['action'] = [
                    'details' => $this->generateUrl('app_order_show', ['id' => $userOrderEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    // /**
    //  * @Route("/new-order", name="app_new_order_index", methods={"GET", "POST"})
    //  */
    // public function index(
    //     Request $request,
    //     CountryEntityRepository $countryEntityRepository
    // ): Response {
    //     $orderStatus = [
    //         'Draft',
    //         'Ready',
    //         'Processing',
    //         'Collected',
    //         'Shipped',
    //         'Cancelled',
    //     ];

    //     // Handle GET request to render the page
    //     if ($request->isMethod('GET')) {
    //         return $this->render('controller/order/index.html.twig', [
    //             'orderStatus' => $orderStatus,
    //         ]);
    //     }

    //     // Handle POST request for DataTables
    //     $draw = (int) $request->get('draw', 0);
    //     $start = (int) $request->get('start', 0);
    //     $length = (int) $request->get('length', 10);
    //     $length = $length < 0 ? 10 : $length;

    //     $filterStatus = $request->get('filter_status', '');
    //     $filterStartDate = $request->get('filter_start_date', '');
    //     $filterEndDate = $request->get('filter_end_date', '');

    //     $qb = $this->em()->getRepository(UserOrderEntity::class)->createQueryBuilder('o');

    //     // Handle search input
    //     $search = $request->get('search');
    //     $searchString = $search['value'] ?? null;
    //     if (!empty($searchString)) {
    //         $qb->andWhere('o.orderId LIKE :query')
    //             ->setParameter('query', '%' . $searchString . '%');
    //     }

    //     // Filter by status
    //     if (!empty($filterStatus)) {
    //         $qb->andWhere('o.status = :status')
    //             ->setParameter('status', $filterStatus);
    //     } else {
    //         $qb->andWhere('o.status != :draft')
    //             ->setParameter('draft', 'Draft');
    //     }

    //     // Filter by date range
    //     if (!empty($filterStartDate) && !empty($filterEndDate)) {
    //         $qb->andWhere($qb->expr()->between('o.createdDate', ':startDate', ':endDate'))
    //             ->setParameter('startDate', $filterStartDate . ' 00:00:00')
    //             ->setParameter('endDate', $filterEndDate . ' 23:59:59');
    //     }

    //     // Pagination and ordering
    //     $qb->setFirstResult($start)
    //         ->setMaxResults($length)
    //         ->orderBy('o.createdDate', 'DESC');

    //     $results = $qb->getQuery()->getResult();

    //     // Count total records for DataTables
    //     $totalRecords = $this->em()->getRepository(UserOrderEntity::class)
    //         ->createQueryBuilder('o')
    //         ->select('COUNT(o.id)')
    //         ->getQuery()
    //         ->getSingleScalarResult();

    //     // Build DataTables response
    //     $data = [];
    //     foreach ($results as $userOrderEntity) {
    //         $data[] = [
    //             'DT_RowId' => $userOrderEntity->getId(),
    //             'orderId' => $userOrderEntity->getOrderId(),
    //             'company' => $userOrderEntity->getSelectedCompany()->getCode(),
    //             'method' => ucwords($userOrderEntity->getMethod()),
    //             'from' => $userOrderEntity->getSourceCountry()->getCode(),
    //             'to' => $userOrderEntity->getDestinationCountry()->getCode(),
    //             'weight' => number_format($userOrderEntity->getFinalWeight(), 3),
    //             'price' => number_format($userOrderEntity->getTotalPrice(), 2),
    //             'user' => [
    //                 'firstName' => $userOrderEntity->getUser()->getFirstName(),
    //                 'lastName' => $userOrderEntity->getUser()->getLastName(),
    //                 'email' => $userOrderEntity->getUser()->getEmail(),
    //                 'mobile' => $userOrderEntity->getUser()->getMobileNumber(),
    //             ],
    //             'status' => $userOrderEntity->getStatus(),
    //             'discounted' => $userOrderEntity->getDiscounted(),
    //             'coupon' => $userOrderEntity->getCouponCode(),
    //             'paymentStatus' => $userOrderEntity->getPaymentStatus(),
    //             'createdDate' => $this->formatToTimezone($userOrderEntity->getCreatedDate()),
    //             'action' => [
    //                 'details' => $this->generateUrl('app_order_show', ['id' => $userOrderEntity->getId()]),
    //             ],
    //         ];
    //     }

    //     return new JsonResponse([
    //         'draw' => $draw,
    //         'recordsTotal' => $totalRecords,
    //         'recordsFiltered' => $totalRecords,
    //         'data' => $data,
    //     ]);
    // }



    /**
     * @Route("/{id}", name="app_order_show", methods={"GET","POST"})
     */
    public function show(
        Request $request,
        UserOrderEntity $userOrderEntity
    ): Response {
        $form = $this->createForm(OrderAddNoteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            /** @var UserEntity $currentUser */
            $currentUser = $this->getUser();
            $currentStatus = $userOrderEntity->getStatus();
            $newStatus = $userOrderEntity->getStatus();
            if (!is_null($formData['changeStatus'])) {
                $newStatus = $formData['changeStatus'];
            }

            $userOrderNoteEntity = new UserOrderNoteEntity();
            $userOrderNoteEntity->setOrder($userOrderEntity);
            $userOrderNoteEntity->setUser($currentUser);
            $userOrderNoteEntity->setDescription($formData['noteDescription']);
            $userOrderNoteEntity->setOldStatus($currentStatus);
            $userOrderNoteEntity->setNewStatus($newStatus);
            $userOrderNoteEntity->setCreatedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($userOrderNoteEntity);
            $entityManager->flush();

            $this->addLog(
                'Order',
                'Note',
                '#' . $userOrderEntity->getOrderId() . ' - Added Note',
                $userOrderNoteEntity->getId()
            );

            if ($currentStatus != $newStatus) {
                $userOrderEntity->setStatus($newStatus);
                $userOrderEntity->setModifiedDate(new \DateTime());
                $this->em()->flush();
            }

            $this->addFlash('message', 'Added note!');

            return $this->redirectToRoute(
                'app_order_show',
                ['id' => $userOrderEntity->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('controller/order/show.html.twig', [
            'order' => $userOrderEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/track-add/{id}", name="app_order_add_tracking", methods={"GET","POST"})
     */
    public function addTrackingCode(
        Request $request,
        UserOrderEntity $userOrderEntity,
        TrackService $trackService
    ) {
        $form = $this->createForm(OrderAddTrackingType::class, $userOrderEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trackingCode = $form->get('trackingCode')->getData();

            try {
                $trackService->addTracking($userOrderEntity, $trackingCode);
            } catch (\Exception $exception) {
                $form->get('trackingCode')->addError(new FormError($exception->getMessage()));
            }

            if ($form->isValid()) {
                $userOrderEntity->setTrackingCode($trackingCode);
                if (is_null($userOrderEntity->getTrackingAddedDate())) {
                    $userOrderEntity->setTrackingAddedDate(new \DateTime());
                } else {
                    $userOrderEntity->setTrackingUpdatedDate(new \DateTime());
                }

                $currentUser = $this->getUser();
                $currentStatus = $userOrderEntity->getStatus();

                $userOrderNoteEntity = new UserOrderNoteEntity();
                $userOrderNoteEntity->setOrder($userOrderEntity);
                $userOrderNoteEntity->setUser($currentUser);
                $userOrderNoteEntity->setDescription('Updated tracking code - ' . $trackingCode);
                $userOrderNoteEntity->setOldStatus($currentStatus);
                $userOrderNoteEntity->setNewStatus($currentStatus);
                $userOrderNoteEntity->setCreatedDate(new \DateTime());

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($userOrderNoteEntity);
                $entityManager->flush();

                $this->addLog(
                    'Order',
                    'Tracking',
                    '#' . $userOrderEntity->getOrderId() . ' - Updated tracking code - ' . $trackingCode
                );

                $trackService->sendCustomerTrackingEmail($userOrderEntity);

                $this->addFlash('message', 'Updated tracking!');

                return $this->redirectToRoute(
                    'app_order_show',
                    ['id' => $userOrderEntity->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->renderForm('controller/order/tracking-form.html.twig', [
            'order' => $userOrderEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/track-show/{id}", name="app_order_show_tracking", methods={"GET"})
     */
    public function showTracking(
        Request $request,
        UserOrderEntity $userOrderEntity,
        TrackService $trackService
    ): Response {
        $shouldUpdate = true;

        if (!is_null($userOrderEntity->getTrackingFetchedDate())) {
            $lastUpdatedTime = $userOrderEntity->getTrackingFetchedDate();
            $currentTime = new \DateTime();
            $interval = $currentTime->getTimestamp() - $lastUpdatedTime->getTimestamp();
            if ($interval < 7200) {
                $shouldUpdate = false;
            }
        }

        if ($shouldUpdate) {
            $trackInfo = $trackService->fetchTrackingInfo($userOrderEntity);
            if (!empty($trackInfo)) {
                $userOrderEntity->setTrackingInfo($trackInfo);
                $userOrderEntity->setTrackingFetchedDate(new \DateTime());
                $this->em()->flush();
            }
        }

        return $this->render('controller/order/tracking-show.html.twig', [
            'order' => $userOrderEntity,
        ]);
    }

    /**
     * @Route("/print/{id}", name="app_order_show_print", methods={"GET"})
     */
    public function showPrint(
        UserOrderEntity $userOrderEntity,
        TransactionService $transactionService
    ): Response {
        $transInfo = $transactionService->getTransactionArray($userOrderEntity->getPaidTransaction());

        return $this->render('controller/order/order-info.html.twig', [
            'transInfo' => $transInfo,
            'order' => $userOrderEntity,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="app_order_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(
        Request $request,
        UserOrderEntity $userOrderEntity
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $userOrderEntity->getId(), $request->request->get('_token'))) {
            try {
                $orderId = $userOrderEntity->getId();
                $orderOrderId = $userOrderEntity->getOrderId();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($userOrderEntity);
                $entityManager->flush();

                $this->addLog(
                    'Order',
                    'Delete',
                    'Deleted Order - ' . $orderOrderId,
                    $orderId
                );

                $this->addFlash('message', 'Deleted Order!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete order!');

                return $this->redirectToRoute(
                    'app_order_show',
                    ['id' => $userOrderEntity->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
