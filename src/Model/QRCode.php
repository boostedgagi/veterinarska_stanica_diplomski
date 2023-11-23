<?php

namespace App\Model;

use App\ApiClient;
use Endroid\QrCode\Builder\BuilderInterface;

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

        return ApiClient::$apiUrl . "/found_pet?id=" . $this->petId;
    }

    public function generateQRCode():string
    {
        $url = $this->makeUrl();
        $possibleQRCode = $this->builder->data($url)->build();

        $qrCodePath = $this->makeFilePath();
        $possibleQRCode->saveToFile($qrCodePath);

        return $qrCodePath;
    }

    private function makeFilePath():string
    {
        return 'public/qr-codes/'.$this->getPetId().'/' . uniqid('', true) . '.png';
    }

}