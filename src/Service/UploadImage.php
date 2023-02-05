<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadImage
{

    private Request $request;
    private object $entity;
    private EntityManagerInterface $em;

    /**
     * @param Request $request
     * @param object $entity
     * @param EntityManagerInterface $em
     */
    public function __construct(Request $request, object $entity, EntityManagerInterface $em)
    {
        $this->request = $request;
        $this->entity = $entity;
        $this->em = $em;
    }

    public function upload(): void
    {
        $uploadPath = 'images';

        $image = $this->request->files->get('image');
        if($this->entity->getImage()!==null){
            unlink($this->entity->getImage());
        }
        if ($image) {
            $extension = $image->guessExtension();

            $newFileName = md5(time() . '-' . mt_rand(10, 100)) . '.' . $extension;

            try {
                $uploadedFile = $image->move($uploadPath, $newFileName);
                $imagePath = $uploadedFile->getPathName();
                $this->entity->setImage($imagePath);
                $this->em->persist($this->entity);
                $this->em->flush();

                unset($uploadedFile);
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }

        }
    }
}