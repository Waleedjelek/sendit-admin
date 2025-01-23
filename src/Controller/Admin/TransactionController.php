<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\UserTransactionEntity;
use App\Repository\CountryEntityRepository;
use App\Service\OrderQuoteService;
use App\Service\TransactionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/transactions")
 * @IsGranted("ROLE_EDITOR")
 */
class TransactionController extends AdminController
{

    private $orderQuoteService;

    public function __construct(OrderQuoteService $orderQuoteService)
    {
        $this->orderQuoteService = $orderQuoteService;
    }
    /**
     * @Route("/", name="app_transaction_index",  methods={"GET","POST"})
     */
    public function index(
        Request $request,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        if ($request->isMethod('get')) {
            $data = $this->orderQuoteService->getTodayOrdersAndQuotes();
            return $this->renderForm('controller/transaction/index.html.twig', [
                    'newOrders' => $data['newOrders'],
                    'newQuotes' => $data['newQuotes'],
            ]);
        }

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        if ($length < 0) {
            $length = 10;
        }

        $filterStartDate = $request->get('filter_start_date', '');
        $filterEndDate = $request->get('filter_end_date', '');

        $qb = $this->em()->getRepository(UserTransactionEntity::class)->createQueryBuilder('t');

        $search = $request->get('search');
        $searchString = null;
        if (null != $search && isset($search['value']) && !empty($search['value'])) {
            $searchString = $search['value'];
        }
        if (!empty($searchString)) {
            $qb->andWhere(' ( t.transId LIKE :query1 ) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }
        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            if ($filterStartDate != $filterEndDate) {
                $qb->andWhere($qb->expr()->between('t.createdDate', ':startDate', ':endDate'));
                $qb->setParameter('startDate', $filterStartDate);
                $qb->setParameter('endDate', $filterEndDate);
            } else {
                $qb->andWhere(' ( t.createdDate LIKE :onlyDate ) ');
                $qb->setParameter('onlyDate', '%'.$filterStartDate.'%');
            }
        }

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        $qb->add('orderBy', ' t.createdDate DESC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(t) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $userTransactionEntity UserTransactionEntity
             */
            foreach ($result as $userTransactionEntity) {
                $ca = [];
                $ca['DT_RowId'] = $userTransactionEntity->getId();
                $ca['transId'] = $userTransactionEntity->getTransId();
                $ca['orderId'] = $userTransactionEntity->getOrder()->getOrderId();
                $ca['amount'] = $userTransactionEntity->getPaidAmount();
                $ca['currency'] = $userTransactionEntity->getPaidCurrency();
                $ca['status'] = $userTransactionEntity->getPaymentStatus();
                $ca['createdDate'] = $this->formatToTimezone($userTransactionEntity->getCreatedDate());
                $ca['action'] = [
                    'details' => $this->generateUrl('app_transaction_show', ['id' => $userTransactionEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/{id}", name="app_transaction_show", methods={"GET"})
     */
    public function show(
        UserTransactionEntity $userTransactionEntity,
        TransactionService $transactionService
    ): Response {
        $transInfo = $transactionService->getTransactionArray($userTransactionEntity);

        return $this->render('controller/transaction/show.html.twig', [
            'transInfo' => $transInfo,
            'trans' => $userTransactionEntity,
        ]);
    }

    /**
     * @Route("/{id}", name="app_transaction_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(
        Request $request,
        UserTransactionEntity $userTransactionEntity
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$userTransactionEntity->getId(), $request->request->get('_token'))) {
            try {
                $transactionId = $userTransactionEntity->getId();
                $transactionTransId = $userTransactionEntity->getTransId();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($userTransactionEntity);
                $entityManager->flush();

                $this->addLog(
                    'Transaction',
                    'Delete',
                    'Deleted Transaction - '.$transactionTransId,
                    $transactionId
                );

                $this->addFlash('message', 'Deleted Transaction!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete transaction!');

                return $this->redirectToRoute('app_transaction_show', ['id' => $userTransactionEntity->getId()],
                    Response::HTTP_SEE_OTHER);
            }
        }

        return $this->redirectToRoute('app_transaction_index', [], Response::HTTP_SEE_OTHER);
    }
}
