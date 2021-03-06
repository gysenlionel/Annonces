<?php

namespace App\Controller;

use App\Entity\Annonces;
use App\Entity\Images;
use App\Form\AnnoncesType;
use App\Form\EditProfilType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

class UsersController extends AbstractController
{
    /**
     * @Route("/users", name="users")
     */
    public function index(): Response
    {
        return $this->render('users/index.html.twig');
    }

    /**
     * @Route("users/annonces/ajout", name="users_annonces_ajout")
     */
    public function ajoutAnnonces(Request $request): Response
    {
        $annonce = new Annonces;

        $form = $this->createForm(AnnoncesType::class, $annonce);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // récupère les images transmises
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                // nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                // copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                // stocke le nom de l img dans la db
                $img = new Images();
                $img->setName($fichier);
                $annonce->addImage($img);
            }

            $annonce->setUsers($this->getUser());
            $annonce->setActive(false);

            $em = $this->getDoctrine()->getManager();
            $em->persist($annonce);
            $em->flush();

            return $this->redirectToRoute('users');
        }

        return $this->render('users/annonces/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("users/annonces/edit/{id}", name="users_annonces_edit", methods={"GET","POST"})
     */
    public function editAnnonces(Request $request, Annonces $annonce): Response
    {
        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // récupère les images transmises
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                // nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                // copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                // stocke le nom de l img dans la db
                $img = new Images();
                $img->setName($fichier);
                $annonce->addImage($img);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('users');
        }

        return $this->render('users/annonces/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("users/profile/editprofile", name="users_profile_editprofile")
     */
    public function editProfile(Request $request): Response
    {

        $user = $this->getUser();
        $form = $this->createForm(EditProfilType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('message', 'Profil mis à jour');
            return $this->redirectToRoute('users');
        }

        return $this->render('users/editprofile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("users/pass/editpass", name="users_pass_editpass")
     */
    public function editPass(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {

        if ($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();

            $user = $this->getUser();

            // On vérifie si les 2 passwords sont identiques
            if ($request->request->get('pass') == $request->request->get('pass2')) {
                $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('pass')));
                $em->flush();
                $this->addFlash('message', 'Mot de passe mis à jour');

                return $this->redirectToRoute('users');
            } else {
                $this->addFlash('error', 'Les deux mots de passe ne sont pas identiques');
            }
        }

        return $this->render('users/editpass.html.twig');
    }

    /**
     * @Route("/users/data", name="users_data")
     */
    public function usersData(): Response
    {
        return $this->render('users/data.html.twig');
    }

    /**
     * @Route("/users/data/download", name="users_data_download")
     */
    public function usersDataDownload(): Response
    {
        // définit les options du pdf
        $pdfOptions = new Options();

        $pdfOptions->set('defaultFont', 'Aria');
        $pdfOptions->setIsRemoteEnabled(true);

        // instancié Dompdf
        $dompdf = new Dompdf($pdfOptions);
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $dompdf->setHttpContext($context);

        // on génère le html
        $html = $this->renderView('users/download.html.twig');

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // On génère un nom de fichier
        $fichier = 'user-data-' . $this->getUser()->getId() . '.pdf';

        // On envoie le pdf au navigateur
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        return new Response();
    }


    /**
     * @Route("/users/annonces/delete/image/{id}", name="users_annonces_delete_image", methods={"DELETE"})
     */
    public function deleteImage(Images $image, Request $request)
    {
        // mettre en true pour l'avoir en tableau assoc
        $data = json_decode($request->getContent(), true);

        // Vérifie si le token est valide
        if ($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])) {
            $nom = $image->getName();
            unlink($this->getParameter('images_directory') . '/' . $nom);

            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            // On répond en json
            return new JsonResponse(['success' => 1]);
        } else {
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }
}