<?php

namespace App\Model;

use App\Entity\Pet;
use App\Repository\PetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;

class QRCode
{
    private int $petId;

    private BuilderInterface $builder;

    /**
     * @param BuilderInterface $builder
     */
    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
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
        $ngrok = $_ENV["NGROK_TUNNEL"];

        return $ngrok . "/found_pet?id=" . $this->petId;
    }

    public function generateQRCode():string
    {
        $url = $this->makeUrl();
        $possibleQRCode = $this->builder->data($url)->build();
        $qrCodePath = 'qr-codes/' . uniqid('', true) . '.png';

        $possibleQRCode->saveToFile($qrCodePath);

        return $qrCodePath;
    }

}