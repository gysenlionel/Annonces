<?php

namespace App\Controller\Admin;

use App\Entity\Annonces;
use App\Repository\AnnoncesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @route("/admin/annonces", name="admin_annonces_")
 * @package App\Controller\Admin
 */
class AnnoncesController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(AnnoncesRepository $annoncesRepo): Response
    {
        return $this->render('admin/annonces/index.html.twig', [
            'annonces' => $annoncesRepo->findAll()
        ]);
    }

    /**
     * @Route("/activer/{id}", name="activer")
     */
    public function activer(Annonces $annonce)
    {
        $annonce->setActive(($annonce->getActive() ? false : true));

        $em = $this->getDoctrine()->getManager();
        $em->persist($annonce);
        $em->flush();

        return new Response("true");
    }

    /**
     * @Route("/supprimer/{id}", name="supprimer")
     */
    public function supprimer(Annonces $annonce)
    {
        $images = $annonce->getImages();

        if ($images) {
            foreach ($images as $image) {
                $nomImage = $this->getParameter("images_directory") . '/' . $image->getName();

                if (file_exists($nomImage)) {
                    unlink($nomImage);
                }
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($annonce);
        $em->flush();

        $this->addFlash('message', 'Annonces supprimée avec succès');
        return $this->redirectToRoute('admin_annonces_home');
    }
}