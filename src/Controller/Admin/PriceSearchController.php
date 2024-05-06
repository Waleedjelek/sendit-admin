<?php

namespace App\Controller\Admin;

use App\Classes\Controller\AdminController;
use App\Entity\ZonePriceEntity;
use App\Form\PriceSearchType;
use App\Repository\CountryEntityRepository;
use App\Service\ZoneService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/price-search")
 * @IsGranted("ROLE_EDITOR")
 */
class PriceSearchController extends AdminController
{
    /**
     * @Route("/", name="app_price_search", methods={"GET"})
     */
    public function search(
        Request $request,
        CountryEntityRepository $countryEntityRepository
    ): Response {
        $countries = $countryEntityRepository->getAll();

        $form = $this->createForm(PriceSearchType::class, null, ['countries' => $countries]);
        $form->handleRequest($request);

        return $this->renderForm('controller/price_search/index.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/data", name="app_price_search_data", methods={"POST"})
     */
    public function searchData(
        Request $request,
        ZoneService $zoneService
    ): Response {
        $draw = $request->get('draw', 0);
        $countryFrom = strtoupper($request->get('from', 'AE'));
        $countryTo = strtoupper($request->get('to', 'IN'));
        $packageType = strtolower($request->get('type', 'package'));
        $weight = $request->get('weight', 1.0);
        $length = ceil($request->get('length', 10));
        $width = ceil($request->get('width', 10));
        $height = ceil($request->get('height', 10));

        $volumeWeight = ($length * $width * $height) / 5000;

        $priceList = $zoneService->getPrices($countryFrom, $countryTo, $packageType, $weight, $volumeWeight);
        $data = [];
        $data['draw'] = $draw;
        $data['recordsFiltered'] = count($priceList);
        $data['recordsTotal'] = count($priceList);
        $data['data'] = [];

        if (!empty($priceList)) {
            /**
             * @var $zonePriceEntity ZonePriceEntity
             */
            $sort = [];
            foreach ($priceList as $zonePriceEntity) {
                $ca = [];
                $ca['DT_RowId'] = $zonePriceEntity->getId();
                $companyEntity = $zonePriceEntity->getZone()->getCompany();
                if ('dom' == $companyEntity->getType()) {
                    $ca['name'] = $companyEntity->getName().' (Dom)';
                } else {
                    $ca['name'] = $companyEntity->getName().' (Int)';
                }
                $ca['method'] = ucfirst($zonePriceEntity->getType());
                if ('package' == $zonePriceEntity->getFor()) {
                    $ca['type'] = 'Package';
                } else {
                    $ca['type'] = 'Document';
                }
                $ca['zone'] = $zonePriceEntity->getZone()->getName();
                $ca['weight'] = number_format($zonePriceEntity->getWeight(), 3);
                $ca['price'] = number_format($zonePriceEntity->getPrice(), 2);
                $data['data'][] = $ca;
                $sort[] = $zonePriceEntity->getPrice();
            }
            array_multisort($sort, SORT_ASC, SORT_NUMERIC, $data['data']);
        }

        return new JsonResponse($data);
    }
}
