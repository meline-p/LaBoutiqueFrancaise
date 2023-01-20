<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Classe\Mail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/nous-contacter', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $this->addFlash('notice', 'Merci de nous avoir contacté. Notre équipe va vous répondre dans les meilleurs délais.');
        
            // Envoie d'un mail àl'adresse mail de laboutique
            $content = "Vous avez reçu une nouvelle demande de contact :<br/><br/>".$form->get('prenom')->getData()." ".$form->get('nom')->getData()."<br/>".$form->get('email')->getData()."<br/><br/> Message : <br/>".$form->get('content')->getData();
            $mail = new Mail();
            $mail->send('pischeddameline@gmail.com', 'La Boutique Française', "Nouvelle demande de contact", $content);
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
