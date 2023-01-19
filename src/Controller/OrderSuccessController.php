<?php

namespace App\Controller;

use App\Entity\Order;
use App\Classe\Cart;
use App\Classe\Mail;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class OrderSuccessController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/commande/merci/{stripeSessionId}', name: 'app_order_success')]
    public function index(Cart $cart, $stripeSessionId, EntityManagerInterface $entityManager): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);
        
        if(!$order || $order->getUser() != $this->getUser()){
            return $this->redirectToRoute('app_home');
        }
        
        if ($order->getState() == 0) {
        //Vider la session 'cart'
        $cart->remove();

        //modifier le statut State de notre commande en mettant 1
        $order->setState(1); // 1 Payée
        $this->entityManager->flush();

        //envoyer un email à notre client pour lui confirmer sa commande
        $mail = new Mail();
        $content = "Bonjour ".$order->getUser()->getFirstname()."<br/>Merci pour votre commande. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.";
        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande La Boutique Française est bien validée.', $content);
        }

        return $this->render('order_success/index.html.twig', [
            //aficher les quelques infos de la commande utilisateur
            'order' => $order
        ]);
    }
}
