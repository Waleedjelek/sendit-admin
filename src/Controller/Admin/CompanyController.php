<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\CompanyEntity;
use App\Entity\ZonePriceEntity;
use App\Form\CompanyEntityType;
use App\Form\ZonePriceExportType;
use App\Service\CompanyService;
use App\Service\OrderQuoteService;
use App\Service\TrackService;
use League\Csv\Writer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/admin/company")
 * @IsGranted("ROLE_EDITOR")
 */
class CompanyController extends AdminController
{

    private $orderQuoteService;

    public function __construct(OrderQuoteService $orderQuoteService)
    {
        $this->orderQuoteService = $orderQuoteService;
    }
    /**
     * @Route("/", name="app_company_index", methods={"GET","POST"})
     */

    //  public function index(Request $request): Response
    //  {
    //      // Handle GET request for rendering the page
    //      if ($request->isMethod('get')) {
    //          return $this->render('controller/company/index.html.twig');
    //      }
     
    //      // DataTables parameters
    //      $draw = $request->get('draw', 0);
    //      $start = $request->get('start', 0);
    //      $length = $request->get('length', 10);
    //      $length = ($length < 0) ? 10 : $length;
     
    //      $search = $request->get('search', []);
    //      $searchString = $search['value'] ?? null;
     
    //      // Query builder for data
    //      $qb = $this->em()->getRepository('App:CompanyEntity')->createQueryBuilder('c');
    //      $qb->setFirstResult($start)
    //          ->setMaxResults($length);
     
    //      // Apply search filter
    //      if (!empty($searchString)) {
    //          $qb->andWhere('c.name LIKE :query ')
    //             ->setParameter('query', '%' . $searchString . '%');
    //      }
     
    //      // Apply sorting
    //      $qb->orderBy('c.name', 'ASC');
    //      $result = $qb->getQuery()->getResult();
     
    //      // Clone query builder for counting total records
    //      $qb2 = clone $qb;
    //      $qb2->resetDQLPart('orderBy');
    //      $qb2->resetDQLPart('having');
    //      $qb2->select('COUNT(c) AS cnt');
    //      $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
    //      $totalCount = $countResult[0]['cnt'];
     
    //      // Prepare response data
    //      $data = [
    //          'draw' => $draw,
    //          'recordsTotal' => $totalCount,
    //          'recordsFiltered' => $totalCount,
    //          'data' => [],
    //      ];
     
    //      if (!empty($result)) {
    //          foreach ($result as $companyEntity) {
    //              $companyEntity->setImagePrefix($request->getSchemeAndHttpHost() . '/uploads');
     
    //              $data['data'][] = [
    //                  'DT_RowId' => $companyEntity->getId(),
    //                  'name' => $companyEntity->getName(),
    //                  'code' => $companyEntity->getCode(),
    //                  'type' => $companyEntity->getType() === 'dom' ? 'Domestic' : 'International',
    //                  'logoImage' => $companyEntity->getLogoImageURL(),
    //                  'logoWidth' => $companyEntity->getLogoWidth(),
    //                  'active' => $companyEntity->isActive(),
    //                  'action' => [
    //                      'details' => $this->generateUrl('app_company_show', ['id' => $companyEntity->getId()]),
    //                  ],
    //              ];
    //          }
    //      }
     
    //      return new JsonResponse($data);
    //  }
    public function index(
        Request $request
    ): Response {
        if ($request->isMethod('get')) {
            // return $this->render('controller/company/index.html.twig');
            $activeCompaniesCount = $this->em()
            ->getRepository('App:CompanyEntity')
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
            $data = $this->orderQuoteService->getTodayOrdersAndQuotes();
        return $this->render('controller/company/index.html.twig', [
            'newOrders' => $data['newOrders'],
            'newQuotes' => $data['newQuotes'],
            'activeCompaniesCount' => $activeCompaniesCount
        ]);
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

        $qb = $this->em()->getRepository('App:CompanyEntity')->createQueryBuilder('c');

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        if (!empty($searchString)) {
            $qb->andWhere(' ( c.name LIKE :query1 OR c.code LIKE :query1 OR c.type LIKE :query1) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }

        $qb->add('orderBy', ' c.name ASC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(c) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $companyEntity CompanyEntity
             */
            foreach ($result as $companyEntity) {
                $companyEntity->setImagePrefix($request->getSchemeAndHttpHost().'/uploads');

                $ca = [];
                $ca['DT_RowId'] = $companyEntity->getId();
                $ca['name'] = $companyEntity->getName();
                $ca['code'] = $companyEntity->getCode();
                if ('dom' == $companyEntity->getType()) {
                    $ca['type'] = 'Domestic';
                } else {
                    $ca['type'] = 'International';
                }
                $ca['logoImage'] = $companyEntity->getLogoImageURL();
                $ca['logoWidth'] = $companyEntity->getLogoWidth();
                $ca['active'] = $companyEntity->isActive();
                $ca['action'] = [
                    'details' => $this->generateUrl('app_company_show', ['id' => $companyEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/new", name="app_company_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(
        Request $request,
        CompanyService $companyService,
        TrackService $trackService
    ): Response {
        $carriers = $trackService->getCarrierList();
        $companyEntity = new CompanyEntity();
        $form = $this->createForm(CompanyEntityType::class, $companyEntity, ['carriers' => $carriers]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoImageFile = $form->get('logoImageFile')->getData();

            if ($logoImageFile instanceof UploadedFile) {
                $logoImage = $companyService->saveLogoImage($logoImageFile);
                $companyEntity->setLogoImage($logoImage);
            }

            $companyEntity->setCreatedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($companyEntity);
            $entityManager->flush();

            $this->addLog(
                'Company',
                'Add',
                'Added Company - ' . $form->get('name')->getData(),
                $companyEntity->getId()
            );
            $this->addFlash('message', 'Added company!');

            return $this->redirectToRoute('app_company_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/company/new.html.twig', [
            'company_entity' => $companyEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_company_show", methods={"GET"})
     */
    public function show(CompanyEntity $companyEntity): Response
    {
        return $this->render('controller/company/show.html.twig', [
            'company' => $companyEntity,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_company_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(
        Request $request,
        CompanyEntity $companyEntity,
        CompanyService $companyService,
        TrackService $trackService
    ): Response {
        $carriers = $trackService->getCarrierList();
        $form = $this->createForm(CompanyEntityType::class, $companyEntity, ['carriers' => $carriers]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoImageFile = $form->get('logoImageFile')->getData();

            if ($logoImageFile instanceof UploadedFile) {
                $logoImage = $companyService->saveLogoImage($logoImageFile);
                if (!is_null($logoImage)) {
                    $companyService->deleteLogoImage($companyEntity->getLogoImage());
                    $companyEntity->setLogoImage($logoImage);
                }
            }
            $companyEntity->setModifiedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addLog(
                'Company',
                'Edit',
                'Edited Company - ' . $form->get('name')->getData(),
                $companyEntity->getId()
            );

            $this->addFlash('message', 'Company updated!');

            return $this->redirectToRoute(
                'app_company_show',
                ['id' => $companyEntity->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('controller/company/edit.html.twig', [
            'company' => $companyEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_company_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(
        Request $request,
        CompanyEntity $companyEntity,
        CompanyService $companyService
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $companyEntity->getId(), $request->request->get('_token'))) {
            try {
                $companyId = $companyEntity->getId();
                $companyName = $companyEntity->getName();
                $companyLogo = $companyEntity->getLogoImage();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($companyEntity);
                $entityManager->flush();
                $companyService->deleteLogoImage($companyLogo);

                $this->addLog(
                    'Company',
                    'Delete',
                    'Deleted Company - ' . $companyName,
                    $companyId
                );

                $this->addFlash('message', 'Deleted company!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete company!');

                return $this->redirectToRoute(
                    'app_company_show',
                    ['id' => $companyEntity->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->redirectToRoute('app_company_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/export", name="app_company_export", methods={"GET","POST"})
     */
    public function export(
        Request $request,
        CompanyEntity $companyEntity,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(ZonePriceExportType::class, null, ['company' => $companyEntity]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qb = $this->em()->getRepository('App:ZoneEntity')->createQueryBuilder('z');
            $qb->setFirstResult(0);
            $qb->setMaxResults(9999);
            $qb->andWhere(" z.company = '" . $companyEntity->getId() . "' ");
            $qb->add('orderBy', ' z.code ASC ');
            $zoneList = $qb->getQuery()->getResult();

            $csvHeaders = ['Weight'];
            $exportRecordTemplate = [
                'Weight' => 0.0,
            ];
            foreach ($zoneList as $zoneEntity) {
                $csvHeaders[] = $zoneEntity->getCode();
                $exportRecordTemplate[$zoneEntity->getCode()] = 0.0;
            }

            $formData = $form->getData();
            $qb = $this->em()->getRepository('App:ZonePriceEntity')->createQueryBuilder('zp');
            $qb->innerJoin('zp.zone', 'jzone');

            $qb->setFirstResult(0);
            $qb->setMaxResults(999999);
            $qb->andWhere(" jzone.company = '" . $companyEntity->getId() . "' ");
            $qb->andWhere(" zp.type = '" . $formData['type'] . "' ");
            $qb->andWhere(" zp.for = '" . $formData['for'] . "' ");
            $qb->add('orderBy', ' zp.weight ASC ');
            $priceList = $qb->getQuery()->getResult();

            $csvRecords = [];
            /** @var ZonePriceEntity $zonePriceEntity */
            foreach ($priceList as $zonePriceEntity) {
                $rowId = number_format($zonePriceEntity->getWeight(), 3);
                if (!isset($csvRecords[$rowId])) {
                    $csvRecords[$rowId] = $exportRecordTemplate;
                    $csvRecords[$rowId]['Weight'] = $zonePriceEntity->getWeight();
                }
                $csvRecords[$rowId][$zonePriceEntity->getZone()->getCode()] = $zonePriceEntity->getPrice();
            }

            $csv = Writer::createFromString('');
            $csv->insertOne($csvHeaders);

            foreach ($csvRecords as $record) {
                $csv->insertOne($record);
            }

            $downloadFilename = date('Ymd')
                . '-' . $companyEntity->getName()
                . '-' . $companyEntity->getType()
                . '-' . $formData['for']
                . '-' . $formData['type'];
            $downloadFilename = $slugger->slug($downloadFilename) . '.csv';

            $this->addLog(
                'Company',
                'Export',
                'Export CSV - ' . $companyEntity->getName(),
                $companyEntity->getId()
            );

            $response = new Response();
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $downloadFilename . '"');
            $response->setContent($csv->toString());

            return $response;
        }

        return $this->renderForm('controller/company/export.html.twig', [
            'company' => $companyEntity,
            'form' => $form,
        ]);
    }
}
