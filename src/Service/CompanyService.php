<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\CompanyEntity;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CompanyService extends BaseService
{
    private Kernel $kernel;

    private string $companyFolder = '/company';

    private Filesystem $filesystem;

    private EntityManagerInterface $entityManager;

    public function __construct(
        Kernel $kernel,
        Filesystem $filesystem,
        EntityManagerInterface $entityManager
    ) {
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
        $this->entityManager = $entityManager;
    }

    public function getCompanyById(string $companyId): ?CompanyEntity
    {
        $repo = $this->entityManager->getRepository(CompanyEntity::class);

        return $repo->find($companyId);
    }

    public function saveLogoImage(UploadedFile $uploadImage): ?string
    {
        $uniqueFilename = uniqid('comp_').'.svg';
        $profileFilename = $this->companyFolder.gmdate('/Y/m/').$uniqueFilename;
        $saveImageFile = $this->getUploadFolder().$profileFilename;

        $this->filesystem->mkdir(dirname($saveImageFile));

        try {
            $uploadImage->move(dirname($saveImageFile), $uniqueFilename);

            return $profileFilename;
        } catch (\Exception $e) {
        }

        return null;
    }

    public function deleteLogoImage(?string $imageName): bool
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
