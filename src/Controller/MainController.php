<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Repository\AnnoncesRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(AnnoncesRepository $annoncesRepo): Response
    {

        return $this->render('main/index.html.twig', [
            'annonces' => $annoncesRepo->findBy(['active' => true], ['created_at' => 'desc']),
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);

        $contact = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (new TemplatedEmail())
                ->from($contact->get('email')->getData())
                ->to('vous@domaine.fr')
                ->subject('Contact depuis le site Annonces')
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'mail' => $contact->get('email')->getData(),
                    'sujet' => $contact->get('sujet')->getData(),
                    'message' => $contact->get('message')->getData()
                ]);
            $mailer->send($email);

            $this->addFlash('message', 'Mail de contact envoyé');
            return $this->redirectToRoute('contact');
        }

        return $this->render('main/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }
}