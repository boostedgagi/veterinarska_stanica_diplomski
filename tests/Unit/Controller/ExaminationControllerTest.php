<?php

namespace App\Tests\Unit\Controller;

use App\ApiClient;
use App\Entity\Examination;
use App\Repository\ExaminationRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Nebkam\FluentTest\RequestBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class ExaminationControllerTest extends WebTestCase
{
//    private KernelBrowser $client;
//
//    protected function setUp(): void
//    {
//        $this->client = static::createClient();
//    }
//
//    public function testGet(): void
//    {
//        $this->client->request('GET', '/examination');
//
//        self::assertResponseIsSuccessful();
//    }
//
//    public function testCreate(): void
//    {
//        $examination = [
//            'name' => 'Test examination',
//            'duration' => 60,
//            'price' => 900,
//        ];
//
//        $response = RequestBuilder::create($this->client)
//            ->setMethod(Request::METHOD_POST)
//            ->setUri('/examination')
//            ->setJsonContent($examination)
//            ->getResponse();
//
//        $createdExamination = $response->getJsonContent();
//        self::assertEquals('Test examination',$createdExamination["name"]);
//        self::assertEquals(60,$createdExamination["duration"]);
//        self::assertEquals(900,$createdExamination["price"]);
//
//        self::assertResponseIsSuccessful();
//    }
}