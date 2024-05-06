<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\ZoneEntity;
use App\Entity\ZonePriceEntity;
use App\Form\ZonePriceEntityType;
use App\Form\ZonePriceImportType;
use App\Repository\CountryEntityRepository;
use App\Service\ZoneService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use League\Csv\Reader;
use League\Csv\Statement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/zone-price/{zoneId}")
 * @ParamConverter("zoneEntity", options={"mapping": {"zoneId":"id"}})
 * @IsGranted("ROLE_EDITOR")
 */
class ZonePriceController extends AdminController
{
    /**
     * @Route("/", name="app_zone_price_index", methods={"POST"})
     */
    public function index(
        Request $request,
        ZoneEntity $zoneEntity
    ): Response {
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

        $qb = $this->em()->getRepository('App:ZonePriceEntity')->createQueryBuilder('zp');
        $qb->setFirstResult($start);
        $qb->setMaxResults($length);
        $qb->andWhere(" zp.zone = '".$zoneEntity->getId()."' ");
        if (!empty($searchString)) {
            $qb->andWhere(' ( zp.weight LIKE :query1   ) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }
        $qb->add('orderBy', ' zp.weight ASC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(zp) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $zonePriceEntity ZonePriceEntity
             */
            foreach ($result as $zonePriceEntity) {
                $ca = [];
                $ca['DT_RowId'] = $zonePriceEntity->getId();
                $ca['type'] = ucwords($zonePriceEntity->getType());
                $ca['for'] = ucwords($zonePriceEntity->getFor());
                $ca['weight'] = number_format($zonePriceEntity->getWeight(), 3);
                $ca['price'] = number_format($zonePriceEntity->getPrice(), 2);
                $ca['action'] = [
                    'edit' => $this->generateUrl('app_zone_price_edit', [
                        'id' => $zonePriceEntity->getId(),
                        'zoneId' => $zoneEntity->getId(),
                    ]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/new", name="app_zone_price_new", methods={"GET","POST"})
     */
    public function new(
        Request $request,
        ZoneEntity $zoneEntity
    ): Response {
        $zonePriceEntity = new ZonePriceEntity();
        $zonePriceEntity->setZone($zoneEntity);

        $form = $this->createForm(ZonePriceEntityType::class, $zonePriceEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $zonePriceEntity->setCreatedDate(new \DateTime());

            try {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($zonePriceEntity);
                $entityManager->flush();
                $this->addFlash('message', 'Added price!');

                $this->addLog(
                    'Zone Price',
                    'Add',
                    'Added price - '.$zoneEntity->getCompany()->getName().' - '.$zoneEntity->getName(),
                    $zonePriceEntity->getId()
                );

                return $this->redirectToRoute('app_zone_show',
                    ['id' => $zoneEntity->getId(), 'companyId' => $zoneEntity->getCompany()->getId()],
                    Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException $exception) {
                $form->addError(new FormError('Duplicate Entry. Please check your input.'));
            }
        }

        return $this->renderForm('controller/zonePrice/new.html.twig', [
            'company' => $zoneEntity->getCompany(),
            'zone' => $zoneEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_zone_price_edit", methods={"GET","POST"})
     */
    public function edit(
        Request $request,
        ZoneEntity $zoneEntity,
        ZonePriceEntity $zonePriceEntity,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $form = $this->createForm(ZonePriceEntityType::class, $zonePriceEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $zonePriceEntity->setModifiedDate(new \DateTime());

            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('message', 'Price updated!');

                $this->addLog(
                    'Zone Price',
                    'Edit',
                    'Edited price - '.$zoneEntity->getCompany()->getName().' - '.$zoneEntity->getName(),
                    $zonePriceEntity->getId()
                );

                return $this->redirectToRoute('app_zone_show',
                    ['id' => $zoneEntity->getId(), 'companyId' => $zoneEntity->getCompany()->getId()],
                    Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException $exception) {
                $form->addError(new FormError('Duplicate Entry. Please check your input.'));
            }
        }

        return $this->renderForm('controller/zonePrice/edit.html.twig', [
            'company' => $zoneEntity->getCompany(),
            'zone' => $zoneEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/import", name="app_zone_price_import", methods={"GET","POST"})
     */
    public function import(
        Request $request,
        ZoneEntity $zoneEntity,
        ZoneService $zoneService
    ): Response {
        $form = $this->createForm(ZonePriceImportType::class, null, ['company' => $zoneEntity->getCompany()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $importFile = $formData['csvFile'];
            $csv = Reader::createFromPath($importFile->getPathname(), 'r');
            $csv->setHeaderOffset(0);
            $headers = $csv->getHeader();
            $headers = array_map('strtolower', $headers);

            if (false === array_search('weight', $headers)) {
                $form->addError(new FormError('`Weight` header missing in CSV'));
            }

            $zoneImportHeader = strtolower($zoneEntity->getCode());
            if (false === array_search(strtolower($zoneImportHeader), $headers)) {
                $form->addError(new FormError('`'.strtoupper($zoneEntity->getCode()).'` header missing in CSV'));
            }

            if ($form->isValid()) {
                $stmt = (new Statement())
                    ->offset(0)
                    ->limit(99999);
                $records = $stmt->process($csv);
                $weights = [];
                $priceRecords = [];
                foreach ($records as $rowIndex => $record) {
                    $record = array_change_key_case($record, CASE_LOWER);
                    $weights[] = $record['weight'];
                    $priceRecords[] = [
                        'weight' => $record['weight'],
                        'price' => $record[$zoneImportHeader],
                    ];
                }

                if (count($weights) !== count(array_unique($weights))) {
                    $form->addError(new FormError('There are duplicates in your weights. Please check and upload'));
                }

                if ($form->isValid()) {
                    $zoneService->importZonePrices($zoneEntity, $formData['type'], $formData['for'], $priceRecords);

                    $this->addLog(
                        'Zone Price',
                        'Import',
                        'Imported price - '.$zoneEntity->getCompany()->getName().' - '.$zoneEntity->getName(),
                        $zoneEntity->getId()
                    );

                    $this->addFlash('message', 'Price imported!');

                    return $this->redirectToRoute('app_zone_show',
                        ['id' => $zoneEntity->getId(), 'companyId' => $zoneEntity->getCompany()->getId()],
                        Response::HTTP_SEE_OTHER);
                }
            }
        }

        return $this->renderForm('controller/zonePrice/import.html.twig', [
            'company' => $zoneEntity->getCompany(),
            'zone' => $zoneEntity,
            'form' => $form,
        ]);
    }
}
