<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\UserEntity;
use App\Entity\UserTransactionEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TransactionService extends BaseService
{
    private EntityManagerInterface $entityManager;

    private ParameterBagInterface $parameterBag;

    private string $transactionPrefix;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

        $this->transactionPrefix = $this->parameterBag->get('app_sendit_transaction_prefix');
    }

    public function generateTransId(int $runIndex): string
    {
        $today = new \DateTime();

        return $this->transactionPrefix.'-'.$today->format('Ym').'-'.str_pad($runIndex, 3, '0', STR_PAD_LEFT);
    }

    public function getRunIndex()
    {
        $repo = $this->entityManager->getRepository(UserTransactionEntity::class);

        $forDateTime = new \DateTime();

        $qb = $repo->createQueryBuilder('q');
        $qb->select('MAX(q.runIndex) AS max_count');
        $qb->andWhere(' q.createdDate LIKE :forDate ');
        $qb->setParameter('forDate', $forDateTime->format('Y-m').'%');
        $countResult = $qb->getQuery()->setFirstResult(0)->getScalarResult();
        $maxCount = $countResult[0]['max_count'];
        if (is_null($maxCount)) {
            $maxCount = 1;
        } else {
            $maxCount = $maxCount + 1;
        }

        return $maxCount;
    }

    public function getUserTransactions(UserEntity $userEntity): array
    {
        $repo = $this->entityManager->getRepository(UserTransactionEntity::class);

        return $repo->findBy([
            'user' => $userEntity,
        ], [
            'createdDate' => 'DESC',
        ]);
    }

    public function getTransactionById(string $transactionId, UserEntity $userEntity): ?UserTransactionEntity
    {
        $repo = $this->entityManager->getRepository(UserTransactionEntity::class);

        return $repo->findOneBy([
            'id' => $transactionId,
            'user' => $userEntity,
        ]);
    }

    public function getTransactionArray(?UserTransactionEntity $userTransactionEntity): array
    {
        $data = [];
        if (is_null($userTransactionEntity)) {
            return $data;
        }
        $data['id'] = $userTransactionEntity->getId();
        $data['transId'] = $userTransactionEntity->getTransId();
        $data['amount'] = number_format($userTransactionEntity->getPaidAmount(), 2);
        $data['currency'] = $userTransactionEntity->getPaidCurrency();
        $data['paymentStatus'] = $userTransactionEntity->getPaymentStatus();
        $data['transactionRef'] = null;
        $tranResponse = $userTransactionEntity->getResponseValues();
        if (isset($tranResponse['order']['transaction']['ref'])) {
            $data['transactionRef'] = $tranResponse['order']['transaction']['ref'];
        }
        $data['paymentMethod'] = null;
        if (isset($tranResponse['order']['paymethod'])) {
            $data['paymentMethod'] = $tranResponse['order']['paymethod'];
        }
        $data['cardInfo'] = null;
        if (isset($tranResponse['order']['card'])) {
            $data['cardInfo'] = $tranResponse['order']['card'];
        }
        $data['createdDate'] = $this->formatToTimezone($userTransactionEntity->getCreatedDate());

        return $data;
    }
}
