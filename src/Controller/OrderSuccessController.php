<?php

namespace App\Controller;

use App\Entity\Order;
use App\Classe\Cart;

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
        
        if (!$order->isIsPaid()) {
        //Vider la session 'cart'
        $cart->remove();
        //modifier le statut isPaid de notre commande en mettant 1
        $order->setIsPaid(1);
        $this->entityManager->flush();
        //envoyer un email à notre client pour lui confirmer sa commande
        }

        return $this->render('order_success/index.html.twig', [
            //aficher les quelques infos de la commande utilisateur
            'order' => $order
        ]);
    }
}
