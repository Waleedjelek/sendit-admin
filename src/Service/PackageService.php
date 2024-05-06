<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\PackageTypeEntity;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PackageService extends BaseService
{
    private Kernel $kernel;

    private EntityManagerInterface $entityManager;

    private $packageFolder = '/package';
    private Filesystem $filesystem;

    public function __construct(
        Kernel $kernel,
        Filesystem $filesystem,
        EntityManagerInterface $entityManager
    ) {
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
        $this->entityManager = $entityManager;
    }

    public function getByCode(string $code, bool $showActive = true): ?PackageTypeEntity
    {
        $qb = $this->entityManager->getRepository(PackageTypeEntity::class)->createQueryBuilder('p');
        $qb->andWhere(' lower(p.code) = lower(:code) ');
        if ($showActive) {
            $qb->andWhere(" p.active = '1' ");
        }
        $qb->setParameter('code', $code);
        $qb->setMaxResults(1);
        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
        }

        return null;
    }

    public function savePackageImage(UploadedFile $uploadImage): ?string
    {
        $uniqueFilename = uniqid('pack_').'.svg';
        $profileFilename = $this->packageFolder.gmdate('/Y/m/').$uniqueFilename;
        $saveImageFile = $this->getUploadFolder().$profileFilename;

        $this->filesystem->mkdir(dirname($saveImageFile));

        try {
            $uploadImage->move(dirname($saveImageFile), $uniqueFilename);

            return $profileFilename;
        } catch (\Exception $e) {
        }

        return null;
    }

    public function saveIconImage(UploadedFile $uploadImage): ?string
    {
        $uniqueFilename = uniqid('icon_').'.svg';
        $profileFilename = $this->packageFolder.gmdate('/Y/m/').$uniqueFilename;
        $saveImageFile = $this->getUploadFolder().$profileFilename;

        $this->filesystem->mkdir(dirname($saveImageFile));

        try {
            $uploadImage->move(dirname($saveImageFile), $uniqueFilename);

            return $profileFilename;
        } catch (\Exception $e) {
        }

        return null;
    }

    public function getImageContent(?string $imageName): ?string
    {
        if (is_null($imageName)) {
            return null;
        }
        $finalImageFile = $this->getUploadFolder().$imageName;
        if (!file_exists($finalImageFile)) {
            return null;
        }

        return file_get_contents($finalImageFile);
    }

    public function deletePackageImage(?string $imageName): bool
    {
        if (is_null($imageName)) {
            return true;
        }
        $deleteImageFile = $this->getUploadFolder().$imageName;
        $this->filesystem->remove($deleteImageFile);

        return true;
    }

    public function getUploadFolder(): string
    {
        return $this->kernel->getProjectDir().'/public/uploads';
    }
}
