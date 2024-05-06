<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\CompanyEntity;
use App\Entity\ZoneEntity;
use App\Form\ZoneEntityType;
use App\Repository\CountryEntityRepository;
use App\Service\ZoneService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/company-zone/{companyId}")
 * @ParamConverter("companyEntity", options={"mapping": {"companyId":"id"}})
 * @IsGranted("ROLE_EDITOR")
 */
class ZoneController extends AdminController
{
    /**
     * @Route("/", name="app_zone_index", methods={"POST"})
     */
    public function index(
        Request $request,
        CompanyEntity $companyEntity
    ): Response {
        $draw = $request->get('draw', 0);
        $start = 0;
        $length = 999;

        $qb = $this->em()->getRepository(ZoneEntity::class)->createQueryBuilder('z');
        $qb->setFirstResult($start);
        $qb->setMaxResults($length);
        $qb->andWhere(" z.company = '".$companyEntity->getId()."' ");
        $qb->add('orderBy', ' z.code ASC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(z) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $zoneEntity ZoneEntity
             */
            foreach ($result as $zoneEntity) {
                $ca = [];
                $ca['DT_RowId'] = $zoneEntity->getId();
                $ca['code'] = $zoneEntity->getCode();
                $ca['name'] = $zoneEntity->getName();
                $ca['minDays'] = $zoneEntity->getMinDays();
                $ca['maxDays'] = $zoneEntity->getMaxDays();
                $ca['active'] = $zoneEntity->isActive();
                $ca['countryCount'] = $zoneEntity->getCountries()->count();
                $ca['action'] = [
                    'show' => $this->generateUrl('app_zone_show', [
                        'id' => $zoneEntity->getId(),
                        'companyId' => $companyEntity->getId(),
                    ]),
                    'edit' => $this->generateUrl('app_zone_edit', [
                        'id' => $zoneEntity->getId(),
                        'companyId' => $companyEntity->getId(),
                    ]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/new", name="app_zone_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(
        Request $request,
        CompanyEntity $companyEntity,
        ZoneService $zoneService,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $countries = $countryEntityRepository->getAll();

        $zoneEntity = new ZoneEntity();
        $zoneEntity->setCompany($companyEntity);
        $form = $this->createForm(ZoneEntityType::class, $zoneEntity, ['countries' => $countries]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $exitingZone = $this->em()->getRepository(ZoneEntity::class)->findOneBy([
                'company' => $companyEntity,
                'code' => $form->get('code')->getData(),
            ]);
            if (!is_null($exitingZone)) {
                $form->get('code')->addError(new FormError('Code already used!'));
            }

            $selectedCountries = $form->get('zoneCountries')->getData();
            $loadedCountries = [];
            if (!empty($selectedCountries)) {
                foreach ($selectedCountries as $countryCode) {
                    $countryEntity = $countryEntityRepository->getByCode($countryCode);
                    $loadedCountries[] = $countryEntity;
                    $availableZones = $zoneService->searchZoneCountryUnique($countryEntity, $companyEntity);
                    if (!empty($availableZones)) {
                        /** @var ZoneEntity $existingZoneEntity */
                        foreach ($availableZones as $existingZoneEntity) {
                            $form->get('zoneCountries')->addError(
                                new FormError($countryEntity->getName().'['.$countryEntity->getCode().']'.
                                    ' present in '.
                                    $existingZoneEntity->getName().'['.$existingZoneEntity->getCode().']'
                                )
                            );
                        }
                    }
                }
            }

            if ($form->isValid()) {
                $countryCollection = $zoneEntity->getCountries();
                $countryCollection->clear();
                if (!empty($loadedCountries)) {
                    foreach ($loadedCountries as $countryEntity) {
                        $countryCollection->add($countryEntity);
                    }
                }
                $zoneEntity->setCountries($countryCollection);
                $zoneEntity->setCreatedDate(new \DateTime());
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($zoneEntity);
                $entityManager->flush();

                $this->addLog(
                    'Zone',
                    'Add',
                    'Added zone - '.$companyEntity->getName().' - '.$zoneEntity->getName(),
                    $zoneEntity->getId()
                );

                $this->addFlash('message', 'Added zone!');

                return $this->redirectToRoute('app_company_show',
                    ['id' => $companyEntity->getId()],
                    Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('controller/zone/new.html.twig', [
            'company' => $companyEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/show/{id}", name="app_zone_show", methods={"GET"})
     */
    public function show(
        Request $request,
        CompanyEntity $companyEntity,
        ZoneEntity $zoneEntity
    ): Response {
        return $this->render('controller/zone/show.html.twig', [
            'company' => $companyEntity,
            'zone' => $zoneEntity,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_zone_edit", methods={"GET","POST"})
     */
    public function edit(
        Request $request,
        CompanyEntity $companyEntity,
        ZoneEntity $zoneEntity,
        ZoneService $zoneService,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $countries = $countryEntityRepository->getAll();

        $form = $this->createForm(ZoneEntityType::class, $zoneEntity, ['countries' => $countries]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qb = $this->em()->getRepository(ZoneEntity::class)->createQueryBuilder('z');
            $qb->andWhere(" z.company = '".$companyEntity->getId()."' ");
            $qb->andWhere(" z.code = '".$form->get('code')->getData()."' ");
            $qb->andWhere(" z.id != '".$zoneEntity->getId()."' ");
            $exitingZone = $qb->getQuery()->getOneOrNullResult();

            if (!is_null($exitingZone)) {
                $form->get('code')->addError(new FormError('Code already used!'));
            }

            $selectedCountries = $form->get('zoneCountries')->getData();
            $loadedCountries = [];
            if (!empty($selectedCountries)) {
                foreach ($selectedCountries as $countryCode) {
                    $countryEntity = $countryEntityRepository->getByCode($countryCode);
                    $loadedCountries[] = $countryEntity;
                    $availableZones = $zoneService->searchZoneCountryUnique($countryEntity, $companyEntity, $zoneEntity);
                    if (!empty($availableZones)) {
                        /** @var ZoneEntity $existingZoneEntity */
                        foreach ($availableZones as $existingZoneEntity) {
                            $form->get('zoneCountries')->addError(
                                new FormError($countryEntity->getName().'['.$countryEntity->getCode().']'.
                                    ' present in '.
                                    $existingZoneEntity->getName().'['.$existingZoneEntity->getCode().']'
                                )
                            );
                        }
                    }
                }
            }

            if ($form->isValid()) {
                $countryCollection = $zoneEntity->getCountries();
                $countryCollection->clear();
                if (!empty($loadedCountries)) {
                    foreach ($loadedCountries as $countryEntity) {
                        $countryCollection->add($countryEntity);
                    }
                }
                $zoneEntity->setCountries($countryCollection);
                $zoneEntity->setModifiedDate(new \DateTime());
                $this->getDoctrine()->getManager()->flush();

                $this->addLog(
                    'Zone',
                    'Edit',
                    'Edited zone - '.$companyEntity->getName().' - '.$zoneEntity->getName(),
                    $zoneEntity->getId()
                );

                $this->addFlash('message', 'Zone updated!');

                return $this->redirectToRoute('app_zone_show',
                    ['companyId' => $companyEntity->getId(), 'id' => $zoneEntity->getId()],
                    Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('controller/zone/edit.html.twig', [
            'company' => $companyEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_zone_delete", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(
        Request $request,
        CompanyEntity $companyEntity,
        ZoneEntity $zoneEntity
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$zoneEntity->getId(), $request->request->get('_token'))) {
            $this->addLog(
                'Zone',
                'Delete',
                'Deleted zone - '.$companyEntity->getName().' - '.$zoneEntity->getName(),
                $zoneEntity->getId()
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($zoneEntity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_company_show', ['id' => $companyEntity->getId()],
            Response::HTTP_SEE_OTHER);
    }
}
