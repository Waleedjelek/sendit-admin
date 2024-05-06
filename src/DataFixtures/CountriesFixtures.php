<?php

namespace App\DataFixtures;

use App\Entity\CountryEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CountriesFixtures extends Fixture implements FixtureGroupInterface
{
    private KernelInterface $appKernel;

    public function __construct(KernelInterface $appKernel)
    {
        $this->appKernel = $appKernel;
    }

    public function load(ObjectManager $manager)
    {
        $flagDirectory = $this->appKernel->getProjectDir().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'flags'.DIRECTORY_SEPARATOR;
        //Downloaded from API
        //URL: https://restcountries.eu/
        $countriesJsonFile = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'countries.json';
        $fileContent = file_get_contents($countriesJsonFile);
        $countryList = json_decode($fileContent, true);

        if (!empty($countryList)) {
            $client = new Client();
            $slugger = new AsciiSlugger();
            foreach ($countryList as $country) {
                $flagFile = strtolower($slugger->slug($country['name'])).'.svg';

                if (!file_exists($flagDirectory.$flagFile)) {
                    $client->request('GET', dump($country['flag']), ['sink' => $flagDirectory.$flagFile]);
                    sleep(1);
                }

                $dailCode = null;
                if (!empty($country['callingCodes'])) {
                    $dailCode = $country['callingCodes'][0];
                }

                $countryEntity = new CountryEntity();
                $countryEntity->setName($country['name']);
                $countryEntity->setCode(strtoupper($country['alpha2Code']));
                $countryEntity->setDialCode($dailCode);
                $countryEntity->setFlag($flagFile);
                $countryEntity->setActive(true);

                $manager->persist($countryEntity);
                $manager->flush();
            }
        }
    }

    public static function getGroups(): array
    {
        return ['countries'];
    }
}
