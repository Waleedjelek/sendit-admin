<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\QuoteEntity;
use App\Entity\QuoteNoteEntity;
use App\Entity\UserEntity;
use App\Form\QuoteAddNoteType;
use App\Repository\CountryEntityRepository;
use App\Service\OrderQuoteService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/quotes")
 * @IsGranted("ROLE_EDITOR")
 */
class QuoteController extends AdminController
{
    private $orderQuoteService;

    public function __construct(OrderQuoteService $orderQuoteService)
    {
        $this->orderQuoteService = $orderQuoteService;
    }
    /**
     * @Route("/", name="app_quote_index",  methods={"GET","POST"})
     */
    public function index(
        Request $request,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $quoteStatus = [
            'New',
            'Spam',
            'Processing',
            'Completed',
        ];

        if ($request->isMethod('get')) {
            $data = $this->orderQuoteService->getTodayOrdersAndQuotes();
            return $this->renderForm('controller/quote/index.html.twig', [
                'newOrders' => $data['newOrders'],
                'newQuotes' => $data['newQuotes'],
                'quoteStatus' => $quoteStatus,
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

        $qb = $this->em()->getRepository(QuoteEntity::class)->createQueryBuilder('q');

        $search = $request->get('search');
        $searchString = null;
        if (null != $search && isset($search['value']) && !empty($search['value'])) {
            $searchString = $search['value'];
        }
        if (!empty($searchString)) {
            $qb->andWhere(' ( q.quoteId LIKE :query1 ) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }
        if (!empty($filterStatus)) {
            $qb->andWhere(' ( q.status LIKE :status ) ');
            $qb->setParameter('status', '%'.$filterStatus.'%');
        }
        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            if ($filterStartDate != $filterEndDate) {
                $qb->andWhere($qb->expr()->between('q.createdDate', ':startDate', ':endDate'));
                $qb->setParameter('startDate', $filterStartDate.' 00:00:00');
                $qb->setParameter('endDate', $filterEndDate.' 23:59:59');
            } else {
                $qb->andWhere(' ( q.createdDate LIKE :onlyDate ) ');
                $qb->setParameter('onlyDate', '%'.$filterStartDate.'%');
            }
        }

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        $qb->add('orderBy', ' q.createdDate DESC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(q) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $quoteEntity QuoteEntity
             */
            foreach ($result as $quoteEntity) {
                $ca = [];
                $ca['DT_RowId'] = $quoteEntity->getId();
                $ca['orderId'] = $quoteEntity->getQuoteId();
                if ('dom' == $quoteEntity->getType()) {
                    $ca['type'] = 'Domestic';
                    $ca['from'] = $quoteEntity->getSourceState();
                    $ca['to'] = $quoteEntity->getDestinationState();
                } else {
                    $ca['type'] = 'International';
                    $ca['from'] = $quoteEntity->getSourceCountry()->getName();
                    $ca['to'] = $quoteEntity->getDestinationCountry()->getName();
                }
                $ca['contactName'] = $quoteEntity->getContactName();
                $ca['status'] = $quoteEntity->getStatus();
                $ca['createdDate'] = $this->formatToTimezone($quoteEntity->getCreatedDate());
                $ca['action'] = [
                    'details' => $this->generateUrl('app_quote_show', ['id' => $quoteEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/{id}", name="app_quote_show", methods={"GET","POST"})
     */
    public function show(
        Request $request,
        QuoteEntity $quoteEntity
    ): Response {
        $form = $this->createForm(QuoteAddNoteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            /** @var UserEntity $currentUser */
            $currentUser = $this->getUser();
            $currentStatus = $quoteEntity->getStatus();
            $newStatus = $quoteEntity->getStatus();
            if (!is_null($formData['changeStatus'])) {
                $newStatus = $formData['changeStatus'];
            }

            $quoteNoteEntity = new QuoteNoteEntity();
            $quoteNoteEntity->setQuote($quoteEntity);
            $quoteNoteEntity->setUser($currentUser);
            $quoteNoteEntity->setDescription($formData['noteDescription']);
            $quoteNoteEntity->setOldStatus($currentStatus);
            $quoteNoteEntity->setNewStatus($newStatus);
            $quoteNoteEntity->setCreatedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($quoteNoteEntity);
            $entityManager->flush();

            $this->addLog(
                'Quote',
                'Note',
                 '#'.$quoteEntity->getQuoteId().' - Added Note',
                $quoteNoteEntity->getId()
            );

            if ($currentStatus != $newStatus) {
                $quoteEntity->setStatus($newStatus);
                $quoteEntity->setModifiedDate(new \DateTime());
                $this->em()->flush();
            }

            $this->addFlash('message', 'Added note!');

            return $this->redirectToRoute('app_quote_show', ['id' => $quoteEntity->getId()],
                Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/quote/show.html.twig', [
            'quote' => $quoteEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="app_quote_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(Request $request, QuoteEntity $quoteEntity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quoteEntity->getId(), $request->request->get('_token'))) {
            try {
                $quoteID = $quoteEntity->getId();
                $quoteQuoteId = $quoteEntity->getQuoteId();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($quoteEntity);
                $entityManager->flush();

                $this->addLog(
                    'Quote',
                    'Delete',
                    'Deleted quote - '.$quoteQuoteId,
                    $quoteID
                );

                $this->addFlash('message', 'Deleted quote successfully!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete quote!');
            }
        }

        return $this->redirectToRoute('app_quote_index', [], Response::HTTP_SEE_OTHER);
    }
}
