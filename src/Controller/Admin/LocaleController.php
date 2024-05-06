<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\LocaleEntity;
use App\Form\LocaleEntityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/locale")
 * @IsGranted("ROLE_EDITOR")
 */
class LocaleController extends AdminController
{
    /**
     * @Route("/", name="app_locale_index", methods={"GET","POST"})
     */
    public function index(
        Request $request
    ): Response {
        if ($request->isMethod('get')) {
            return $this->render('controller/locale/index.html.twig');
        }

        $draw = $request->get('draw', 0);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        if ($length < 0) {
            $length = 10;
        }

        $qb = $this->em()->getRepository(LocaleEntity::class)->createQueryBuilder('l');

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        $qb->add('orderBy', ' l.code ASC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(l) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $localeEntity LocaleEntity
             */
            foreach ($result as $localeEntity) {
                $ca = [];
                $ca['DT_RowId'] = $localeEntity->getId();
                $ca['code'] = $localeEntity->getCode();
                $ca['action'] = [
                    'edit' => $this->generateUrl('app_locale_edit', ['id' => $localeEntity->getId()]),
                ];
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/new", name="app_locale_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(
        Request $request
    ): Response {
        $localEntity = new LocaleEntity();
        $form = $this->createForm(LocaleEntityType::class, $localEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $localEntity->setCreatedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($localEntity);
            $entityManager->flush();

            $this->addLog(
                'Locale',
                'Add',
                'Added Locale - '.$form->get('code')->getData(),
                $localEntity->getId()
            );
            $this->addFlash('message', 'Added Locale!');

            return $this->redirectToRoute('app_locale_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/locale/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_locale_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(
        Request $request,
        LocaleEntity $localEntity
    ): Response {
        $form = $this->createForm(LocaleEntityType::class, $localEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $localEntity->setModifiedDate(new \DateTime());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addLog(
                'Locale',
                'Edit',
                'Edited Locale - '.$form->get('code')->getData(),
                $localEntity->getId()
            );

            $this->addFlash('message', 'Locale updated!');

            return $this->redirectToRoute('app_locale_index', [],
                Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('controller/locale/edit.html.twig', [
            'locale' => $localEntity,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_locale_delete", methods={"POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(
        Request $request,
        LocaleEntity $localEntity
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$localEntity->getId(), $request->request->get('_token'))) {
            try {
                $localeId = $localEntity->getId();
                $companyCode = $localEntity->getCode();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($localEntity);
                $entityManager->flush();

                $this->addLog(
                    'Company',
                    'Delete',
                    'Deleted Company - '.$companyCode,
                    $localeId
                );

                $this->addFlash('message', 'Deleted locale!');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Unable to delete locale!');

                return $this->redirectToRoute('app_locale_index', [],
                    Response::HTTP_SEE_OTHER);
            }
        }

        return $this->redirectToRoute('app_locale_index', [], Response::HTTP_SEE_OTHER);
    }
}
