<?php

namespace App\Tests\Unit\Controller;

use App\ApiClient;
use App\Entity\Examination;
use JsonException;
use Nebkam\FluentTest\RequestBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class ExaminationControllerTest extends WebTestCase
{
    public function testGetOneExamination(): void
    {
        $client = static::createClient();

        $examination = new Examination();
        $examination
            ->setName('pregled')
            ->setDuration(60)
            ->setPrice(1000);

        $response = RequestBuilder::create($client)
            ->setMethod(Request::METHOD_POST)
            ->setUri(ApiClient::$apiUrl.'/examination/')
            ->setJsonContent($examination)
            ->getResponse();

        dump($response->getJsonContent());
    }
}