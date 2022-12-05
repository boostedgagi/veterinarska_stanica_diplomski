<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadImage
{

    private Request $request;
    private int $id;
    private object $entity;
    private EntityManagerInterface $em;

    /**
     * @param Request $request
     * @param int $id
     * @param object $entity
     * @param EntityManagerInterface $em
     */
    public function __construct(Request $request, int $id, object $entity, EntityManagerInterface $em)
    {
        $this->request = $request;
        $this->id = $id;
        $this->entity = $entity;
        $this->em = $em;
    }

    public function upload(): bool
    {
        $uploadPath = 'images';

        $image = $this->request->files->get('image');
        $entity = $this->em->getRepository($this->entity::class)->find($this->id);

        if ($image) {
            $extension = $image->guessExtension();

            $newFileName = md5(time() . '-' . mt_rand(10, 100)) . '.' . $extension;

            try {
                $uploadedFile = $image->move($uploadPath, $newFileName);
                $imagePath = $uploadedFile->getPathName();
                $entity->setImage($imagePath);

                $this->em->persist($entity);
                $this->em->flush();

                unset($uploadedFile);
                return true;
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }

        }
        return false;
    }
}