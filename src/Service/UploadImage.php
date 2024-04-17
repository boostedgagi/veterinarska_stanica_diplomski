<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class UploadImage
{
    private Filesystem $fs;
    public function __construct(
        private readonly Request $request,
        private readonly User|Pet $entity,
        private readonly EntityManagerInterface $em,

    )
    {
        $this->fs = new Filesystem();
    }

    public function upload(): void
    {
        $uploadPath = 'images';

        $image = $this->request->files->get('image');
        if($this->entity->getImage()!==null){
            $this->fs->remove($this->entity->getImage());
        }
        if ($image) {
            $extension = $image->guessExtension();

            $newFileName = md5(time() . '-' . mt_rand(10, 100)) . '.' . $extension;

            try {
                $uploadedFile = $image->move($uploadPath, $newFileName);
                $imagePath = $uploadedFile->getPathName();

                $this->entity->setImage($imagePath);

//                $this->em->persist($this->entity);
                $this->em->flush();

                unset($uploadedFile);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }

        }
    }
}