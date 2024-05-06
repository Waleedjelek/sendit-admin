<?php

namespace App\Service;

use App\Classes\Service\BaseService;
use App\Entity\UserOrderEntity;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TrackService extends BaseService
{
    private EntityManagerInterface $entityManager;

    private ParameterBagInterface $parameterBag;

    private string $trackingAPIKey;

    private string $marketingWebsiteURL;

    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;

        $this->trackingAPIKey = $this->parameterBag->get('app_17_track_api_key');
        $this->marketingWebsiteURL = $parameterBag->get('app_sendit_marketing_website_url');

        $this->emailService = $emailService;
    }

    /**
     * @return array
     */
    public function getCarrierList()
    {
        $returnValue = [];
        $client = new Client();
        try {
            $response = $client->get('https://www.17track.net/en/apicarrier');
        } catch (GuzzleException $exception) {
            return $returnValue;
        }
        $responseJson = $response->getBody()->getContents();
        $carrierList = json_decode($responseJson, true);
        if (!empty($carrierList)) {
            foreach ($carrierList as $item) {
                $returnValue[$item['_name'].' ('.$item['key'].')'] = $item['key'];
            }
        }

        return $returnValue;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function addTracking(UserOrderEntity $userOrderEntity, string $trackingCode)
    {
        $client = new Client([
            'base_uri' => 'https://api.17track.net',
            'timeout' => 2.0,
            'cookies' => true,
        ]);

        $data[] = [
            'number' => $trackingCode,
            'carrier' => $userOrderEntity->getSelectedCompany()->getCarrierCode(),
        ];

        try {
            $response = $client->request('POST', '/track/v1/register', [
                'json' => $data,
                'headers' => ['17token' => $this->trackingAPIKey],
            ]);
            $responseText = $response->getBody()->getContents();
            $data = json_decode($responseText, true);

            if (0 == $data['code']) {
                return true;
            }

//            if (0 == $data['code'] && 0 != count($data['data']['accepted'])) {
//                return true;
//            }

            throw new \Exception('Code already added!');
        } catch (GuzzleException $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @return array|mixed
     */
    public function fetchTrackingInfo(UserOrderEntity $userOrderEntity)
    {
        $client = new Client([
            'base_uri' => 'https://api.17track.net',
            'timeout' => 2.0,
            'cookies' => true,
        ]);

        $data[] = [
            'number' => $userOrderEntity->getTrackingCode(),
            'carrier' => $userOrderEntity->getSelectedCompany()->getCarrierCode(),
        ];

        try {
            $response = $client->request('POST', '/track/v1/gettrackinfo', [
                'json' => $data,
                'headers' => ['17token' => $this->trackingAPIKey],
            ]);
            $responseText = $response->getBody()->getContents();
            $data = json_decode($responseText, true);

            if (0 == $data['code'] && 0 != count($data['data']['accepted'])) {
                if (isset($data['data']['accepted'][0]['track']) && !empty($data['data']['accepted'][0]['track'])) {
                    return $data['data']['accepted'][0]['track'];
                }
            }
        } catch (GuzzleException $exception) {
        }

        return [];
    }

    public function sendCustomerTrackingEmail(UserOrderEntity $userOrderEntity)
    {
        $userEntity = $userOrderEntity->getUser();

        $email = new TemplatedEmail();
        $email->addTo($userEntity->getEmail());
        $email->subject('Send it - Track Order #'.$userOrderEntity->getOrderId());
        $email->htmlTemplate('emails/user-track.html.twig');
        $email->context([
            'contactName' => $userEntity->getFirstName(),
            'orderId' => $userOrderEntity->getOrderId(),
            'trackingLink' => $this->marketingWebsiteURL.'/#track-order/'.$userOrderEntity->getOrderId(),
            'email_sent_to' => $userEntity->getEmail(),
        ]);
        $this->emailService->send($email);
    }
}
