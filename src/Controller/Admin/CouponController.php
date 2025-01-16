<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\CouponEntity;
use App\Form\CouponEntityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/coupon")
 * @IsGranted("ROLE_EDITOR")
 */
class CouponController extends AdminController
{
    /**
     * @Route("/", name="app_coupon_index", methods={"GET","POST"})
     */
    // public function index(
    //     Request $request
    // ): Response {
    //     if ($request->isMethod('get')) {
    //         return $this->render('controller/coupon/index.html.twig');
    //     }

    //     $draw = $request->get('draw', 0);
    //     $start = $request->get('start', 0);
    //     $length = $request->get('length', 10);
    //     if ($length < 0) {
    //         $length = 10;
    //     }

    //     $search = $request->get('search');
    //     $searchString = null;
    //     if (null != $search && isset($search['value']) && !empty($search['value'])) {
    //         $searchString = $search['value'];
    //     }

    //     $qb = $this->em()->getRepository('App:CouponEntity')->createQueryBuilder('c');

    //     $qb->setFirstResult($start);
    //     $qb->setMaxResults($length);

    //     if (!empty($searchString)) {
    //         $qb->andWhere(' ( c.coupon LIKE :query1 ) ');
    //         $qb->setParameter('query1', '%'.$searchString.'%');
    //     }

    //     $qb->add('orderBy', ' c.coupon ASC ');
    //     $result = $qb->getQuery()->getResult();

    //     $qb2 = clone $qb; // don't modify existing query
    //     $qb2->resetDQLPart('orderBy');
    //     $qb2->resetDQLPart('having');
    //     $qb2->select('COUNT(c) AS cnt');
    //     $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
    //     $totalCount = $countResult[0]['cnt'];

    //     $data = [];
    //     $data['draw'] = $draw;
    //     $data['recordsFiltered'] = $totalCount;
    //     $data['recordsTotal'] = $totalCount;
    //     $data['data'] = [];

    //     if (!empty($result)) {
    //         /**
    //          * @var $couponEntity couponEntity
    //          */
    //         foreach ($result as $couponEntity) {

    //             $ca = [];
    //             $ca['DT_RowId'] = $couponEntity->getId();
    //             $ca['coupon'] = $couponEntity->getCoupon();
    //             $ca['discount'] = $couponEntity->getDiscount();
    //             $ca['active'] = $couponEntity->isActive();
    //             $ca['action'] = [
    //                 'details' => $this->generateUrl('app_coupon_show', ['id' => $couponEntity->getId()]),
    //             ];
    //             $data['data'][] = $ca;
    //         }
    //     }

    //     return new JsonResponse($data);
    // }

    public function index(Request $request): Response
    {
        if ($request->isMethod('get')) {
            return $this->render('controller/coupon/index.html.twig');
        }

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $length = $length < 0 ? 10 : $length;

        $search = $request->get('search');
        $searchString = $search['value'] ?? null;

        $qb = $this->em()->getRepository('App:CouponEntity')->createQueryBuilder('c');
        $qb->setFirstResult($start)
            ->setMaxResults($length);

        if (!empty($searchString)) {
            $qb->andWhere('c.coupon LIKE :query1')
                ->setParameter('query1', '%' . $searchString . '%');
        }

        $qb->orderBy('c.coupon', 'ASC');
        $result = $qb->getQuery()->getResult();

        $totalCount = $this->em()->getRepository('App:CouponEntity')
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $data = [
            'draw' => $draw,
            'recordsFiltered' => $totalCount,
            'recordsTotal' => $totalCount,
            'data' => [],
        ];

        foreach ($result as $couponEntity) {
            $data['data'][] = [
                'DT_RowId' => $couponEntity->getId(),
                'coupon' => $couponEntity->getCoupon(),
                'discount' => $couponEntity->getDiscount(),
                'active' => $couponEntity->isActive(),
                'action' => [
                    'details' => $this->generateUrl('app_coupon_show', ['id' => $couponEntity->getId()]),
                ],
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/new", name="app_coupon_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(
        Request $request
    ): Response {
        $couponEntity = new CouponEntity();
        $form = $this->createForm(CouponEntityType::class, $couponEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $couponEntity->setCreatedDate(new \DateTime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($couponEntity);
            $entityManager->flush();
            $this->addFlash('message', 'Added coupon!');
            return $this->redirectToRoute('app_coupon_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('controller/coupon/new.html.twig', [
            'coupon' => $couponEntity,
            'discount' => $couponEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_coupon_show", methods={"GET"})
     */
    public function show(CouponEntity $couponEntity): Response
    {
        return $this->render('controller/coupon/show.html.twig', [
            'coupon' => $couponEntity,
            'discount' => $couponEntity,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_coupon_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(
        Request $request,
        CouponEntity $couponEntity
    ): Response {
        $form = $this->createForm(CouponEntityType::class, $couponEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('app_coupon_index', [], Response::HTTP_SEE_OTHER);

            $this->addFlash('message', 'Coupon updated!');

            //     return $this->redirectToRoute('app_coupon_show', ['id' => $couponEntity->getId()],
            //         Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/coupon/edit.html.twig', [
            'coupon' => $couponEntity,
            'discount' => $couponEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_coupon_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(
        Request $request,
        CouponEntity $couponEntity
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $couponEntity->getId(), $request->request->get('_token'))) {
            try {
                $couponId = $couponEntity->getId();
                $couponCode = $couponEntity->getCoupon();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($couponEntity);
                $entityManager->flush();

                $this->addLog(
                    'Coupon',
                    'Delete',
                    'Deleted Coupon - ' . $couponCode,
                    $couponId
                );

                $this->addFlash('message', 'Deleted coupon!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete coupon!');

                return $this->redirectToRoute(
                    'app_coupon_show',
                    ['id' => $couponEntity->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->redirectToRoute('app_coupon_index', [], Response::HTTP_SEE_OTHER);
    }
}
