<?php

namespace App\Controller\Admin;

use App\Classes\Controller\BaseController;
use App\Entity\PackageTypeEntity;
use App\Form\PackageTypeEntityType;
use App\Service\PackageService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/package-types")
 * @IsGranted("ROLE_EDITOR")
 */
class PackageTypeController extends BaseController
{
    /**
     * @Route("/", name="app_package_type_index", methods={"GET","POST"})
     */
    public function index(Request $request): Response
    {
        if ($request->isMethod('get')) {
            return $this->render('controller/package_type/index.html.twig');
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

        $qb = $this->em()->getRepository(PackageTypeEntity::class)->createQueryBuilder('pt');

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        if (!empty($searchString)) {
            $qb->andWhere(' ( pt.name LIKE :query1 OR  pt.code LIKE :query1 OR pt.type LIKE :query1 ) ');
            $qb->setParameter('query1', '%'.$searchString.'%');
        }

        $qb->add('orderBy', ' pt.sortOrder ASC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(pt) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $packageTypeEntity PackageTypeEntity
             */
            foreach ($result as $packageTypeEntity) {
                $ca = [];
                $ca['DT_RowId'] = $packageTypeEntity->getId();
                $ca['name'] = $packageTypeEntity->getName();
                $ca['code'] = $packageTypeEntity->getCode();
                $ca['maxWeight'] = number_format($packageTypeEntity->getMaxWeight(), 3);
                $ca['type'] = ucwords($packageTypeEntity->getType());
                $ca['sort'] = $packageTypeEntity->getSortOrder();
                $ca['active'] = $packageTypeEntity->isActive();
                $ca['action'] = [
                    'details' => $this->generateUrl('app_package_type_show', ['id' => $packageTypeEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/new", name="app_package_type_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(
        Request $request,
        PackageService $packageService
    ): Response {
        $packageTypeEntity = new PackageTypeEntity();
        $form = $this->createForm(PackageTypeEntityType::class, $packageTypeEntity, ['edit_mode' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $packageImageFile = $form->get('packageImageFile')->getData();
            if ($packageImageFile instanceof UploadedFile) {
                $packageImage = $packageService->savePackageImage($packageImageFile);
                $packageTypeEntity->setPackageImage($packageImage);
            }

            $iconImageFile = $form->get('iconImageFile')->getData();
            if ($iconImageFile instanceof UploadedFile) {
                $iconImage = $packageService->saveIconImage($iconImageFile);
                $packageTypeEntity->setIconImage($iconImage);
            }

            $packageTypeEntity->setCreatedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($packageTypeEntity);
            $entityManager->flush();

            $this->addLog(
                'Package Type',
                'Add',
                'Added package type - '.$packageTypeEntity->getName(),
                $packageTypeEntity->getId()
            );

            $this->addFlash('message', 'Added package type!');

            return $this->redirectToRoute('app_package_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/package_type/new.html.twig', [
            'package_type' => $packageTypeEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_package_type_show", methods={"GET"})
     */
    public function show(
        PackageTypeEntity $packageTypeEntity
    ): Response {
        return $this->render('controller/package_type/show.html.twig', [
            'package_type' => $packageTypeEntity,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_package_type_edit", methods={"GET","POST"})
     */
    public function edit(
        Request $request,
        PackageTypeEntity $packageTypeEntity,
        PackageService $packageService
    ): Response {
        $form = $this->createForm(PackageTypeEntityType::class, $packageTypeEntity, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $packageImageFile = $form->get('packageImageFile')->getData();

            if ($packageImageFile instanceof UploadedFile) {
                $packageImage = $packageService->savePackageImage($packageImageFile);
                if (!is_null($packageImage)) {
                    $packageService->deletePackageImage($packageTypeEntity->getPackageImage());
                    $packageTypeEntity->setPackageImage($packageImage);
                }
            }

            $iconImageFile = $form->get('iconImageFile')->getData();
            if ($iconImageFile instanceof UploadedFile) {
                $iconImage = $packageService->saveIconImage($iconImageFile);
                if (!is_null($iconImage)) {
                    $packageService->deletePackageImage($packageTypeEntity->getIconImage());
                    $packageTypeEntity->setIconImage($iconImage);
                }
            }

            $packageTypeEntity->setModifiedDate(new \DateTime());

            $this->getDoctrine()->getManager()->flush();

            $this->addLog(
                'Package Type',
                'Edit',
                'Edited package type - '.$packageTypeEntity->getName(),
                $packageTypeEntity->getId()
            );

            $this->addFlash('message', 'Updated package type!');

            return $this->redirectToRoute('app_package_type_show', ['id' => $packageTypeEntity->getId()],
                Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/package_type/edit.html.twig', [
            'package_type' => $packageTypeEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="app_package_type_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(
        Request $request,
        PackageTypeEntity $packageTypeEntity,
        PackageService $packageService
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$packageTypeEntity->getId(), $request->request->get('_token'))) {
            try {
                $packageId = $packageTypeEntity->getId();
                $packageName = $packageTypeEntity->getName();
                $packageImage = $packageTypeEntity->getPackageImage();
                $iconImage = $packageTypeEntity->getIconImage();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($packageTypeEntity);
                $entityManager->flush();
                $packageService->deletePackageImage($packageImage);
                $packageService->deletePackageImage($iconImage);

                $this->addLog(
                    'Package Type',
                    'Delete',
                    'Deleted package type - '.$packageName,
                    $packageId
                );

                $this->addFlash('message', 'Deleted package type!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete package type!');

                return $this->redirectToRoute('app_package_type_show', ['id' => $packageTypeEntity->getId()],
                    Response::HTTP_SEE_OTHER);
            }
        }

        $this->addFlash('message', 'Deleted package type!');

        return $this->redirectToRoute('app_package_type_index', [], Response::HTTP_SEE_OTHER);
    }
}
