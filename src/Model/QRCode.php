<?php

namespace App\Model;

use App\ApiClient;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Symfony\Component\Filesystem\Filesystem;

class QRCode
{
    private int $petId;

    public function __construct(
        private readonly BuilderInterface $builder
    )
    {
    }

    public function setPetId(int $petId): void
    {
        $this->petId = $petId;
    }

    public function getPetId():int
    {
        return $this->petId;
    }

    private function makeUrl():string
    {
        return ApiClient::$websiteUrl . "/found_pet?id=" . $this->petId;
    }

    public function generate():ResultInterface
    {
        $url = $this->makeUrl();
        return $this->builder->data($url)->build();
    }

}