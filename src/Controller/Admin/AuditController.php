<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\AuditLogEntity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/auditlog")
 * @IsGranted("ROLE_EDITOR")
 */
class AuditController extends AdminController
{
    /**
     * @Route("/", name="app_audit_index", methods={"GET","POST"})
     */
    public function index(
        Request $request
    ): Response {
        if ($request->isMethod('get')) {
            return $this->render('controller/audit/index.html.twig');
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

        $qb = $this->em()->getRepository(AuditLogEntity::class)->createQueryBuilder('a');

        $qb->setFirstResult($start);
        $qb->setMaxResults($length);

        if (!empty($searchString)) {
            $qb->setParameter('query1', '%'.$searchString.'%');
            $qb->andWhere(' ( a.module LIKE :query1 OR  a.action LIKE :query1 OR  a.description LIKE :query1 OR  a.ip LIKE :query1 ) ');
        }
        $qb->andWhere($qb->expr()->gte('a.actionDate', ':date_until_from'));
        $qb->setParameter(':date_until_from', date('Y-m-d', strtotime('-4 week')).' 00:00:00');

        $qb->add('orderBy', ' a.actionDate DESC ');
        $result = $qb->getQuery()->getResult();

        $qb2 = clone $qb; // don't modify existing query
        $qb2->resetDQLPart('orderBy');
        $qb2->resetDQLPart('having');
        $qb2->select('COUNT(a) AS cnt');
        $countResult = $qb2->getQuery()->setFirstResult(0)->getScalarResult();
        $totalCount = $countResult[0]['cnt'];

        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = $totalCount;
        $data['recordsTotal'] = $totalCount;
        $data['data'] = [];

        if (!empty($result)) {
            /**
             * @var $auditEntity AuditLogEntity
             */
            foreach ($result as $auditEntity) {
                $ca = [];
                $ca['DT_RowId'] = $auditEntity->getId();
                $ca['module'] = $auditEntity->getModule();
                $ca['action'] = $auditEntity->getAction();
                $ca['description'] = $auditEntity->getDescription();
                $ca['ip'] = $auditEntity->getIp();
                $ca['user'] = '-';
                if (!is_null($auditEntity->getUser())) {
                    $ca['user'] = $auditEntity->getUser()->getFirstName();
                }
                $ca['date'] = $this->formatToTimezone($auditEntity->getActionDate());
                $data['data'][] = $ca;
            }
        }

        return new JsonResponse($data);
    }
}
