<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\Product;

use Stripe\Stripe;
use Stripe\Checkout\Session;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class StripeController extends AbstractController
{

    private $entityManager;
 
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/commande/create-session/{reference}', name: 'app_stripe_create_session')]
    public function index(Cart $cart, $reference): Response
    {

        $products_for_stripe =[];
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';

        $order = $this->entityManager->getRepository(Order::class)->findOneByReference($reference);

        if (!$order) {
            return $this->redirectToRoute('order');
        }

        foreach ($order->getOrderDetails()->getValues() as $product){
            $product_object = $this->entityManager->getRepository(Product::class)->findOneByName($product->getProduct());
            $products_for_stripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product->getPrice(),
                    'product_data' => [
                        'name' => $product->getProduct(),
                        'images' => [$YOUR_DOMAIN."/uploads/".$product_object->getIllustration()],
                    ],
                ],
                  'quantity' => $product->getQuantity(),
            ];
        }

        $products_for_stripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice() * 100,
                'product_data' => [
                    'name' => $order->getCarrierName(),
                    'images' => [$YOUR_DOMAIN],
                ],
            ],
              'quantity' => 1
        ];

        Stripe::setApiKey('sk_test_51MRbb3J3dVGUngyy4GU8VZjoTWeInBmz6WlZXqhpVHmSn9ZpqPQELvsMfAiY51rAeQ1zuT1IHhBifbWLb43nvaP900cjjaMHgy');

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [
                $products_for_stripe
            ],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/success.html',
            'cancel_url' => $YOUR_DOMAIN . '/cancel.html',
        ]);

        return $this->redirect($checkout_session->url);
    }

}
