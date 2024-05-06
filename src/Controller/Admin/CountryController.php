<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\CountryEntity;
use App\Form\CountryEntityType;
use App\Repository\CountryEntityRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/country")
 */
class CountryController extends AdminController
{
    /**
     * @Route("/", name="app_country_index", methods={"GET","POST"})
     */
    public function index(
        Request $request,
        Packages $assetsManager,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        if ($request->isMethod('get')) {
            return $this->render('controller/country/index.html.twig');
        }

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        if ($length < 0) {
            $length = 10;
        }

        $search = $request->get('search');
        $searchString = null;
        if (null != $search && isset($search['value']) && !empty($search['value'])) {
            $searchString = $search['value'];
        }

        $qb = $countryEntityRepository->createQueryBuilder('m');
        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        if (!empty($searchString)) {
            $qb->andWhere(' ( m.name LIKE :query1 OR  m.code LIKE :query1 OR m.dialCode LIKE :query1) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }

        $qb->add('orderBy', 'm.sortOrder DESC, m.name ASC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(m) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $countryEntity CountryEntity
             */
            foreach ($result as $countryEntity) {
                $ca = [];
                $ca['DT_RowId'] = $countryEntity->getId();
                $ca['name'] = $countryEntity->getName();
                $ca['code'] = $countryEntity->getCode();
                $ca['dailCode'] = $countryEntity->getDialCode();
                $ca['sortOrder'] = $countryEntity->getSortOrder();
                $ca['flag'] = $assetsManager->getUrl('build/images/flags/'.$countryEntity->getFlag());
                $ca['active'] = $countryEntity->isActive();
                $ca['action'] = [
                    'edit' => $this->generateUrl('app_country_edit', ['id' => $countryEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/{id}/edit", name="app_country_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CountryEntity $countryEntity): Response
    {
        $form = $this->createForm(CountryEntityType::class, $countryEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addLog(
                'Country',
                'Edit',
                'Edited Company - '.$countryEntity->getName(),
                $countryEntity->getId()
            );

            $this->addFlash('message', 'Updated country!');

            return $this->redirectToRoute('app_country_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/country/edit.html.twig', [
            'country_entity' => $countryEntity,
            'form' => $form,
        ]);
    }
}
