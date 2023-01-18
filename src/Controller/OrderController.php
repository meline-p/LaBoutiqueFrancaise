<?php

namespace App\Controller;

use App\Form\OrderType;
use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\OrderDetails;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }


    /**
     * @Route("/commande", name="app_order")
     */
    public function index(Cart $cart, Request $request): Response
    {
        if (!$this->getUser()->getAdresses()->getValues()){
            return $this->redirectToRoute('app_account_adress_add');
        }

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            dd($form->getData());
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getFull()
        ]);
    }


    /**
     * @Route("/commande/recapitulatif", name="app_order_recap" , methods={"POST"})
     */
    public function add(Cart $cart, Request $request): Response
    {
        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $date = new \DateTimeImmutable();
            $carriers = $form->get('carriers')->getData();
            $delivery = $form->get('adresses')->getData();
            $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
            $delivery_content .= '<br/>'.$delivery->getPhone();

            if($delivery->getCompany()){
                $delivery_content .= '<br/>'.$delivery->getCompany();
            }

            $delivery_content .= '<br/>'.$delivery->getAdress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();;
            $delivery_content .= '<br/>'.$delivery->getCountry();

            //Enregistrer ma commande Order()
            $order = new Order();
            $reference = $date->format('dmY').'-'.uniqid();
            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($delivery_content);
            $order->setIsPaid(0);

            $this->entityManager->persist($order);

            //Enregistrer mes produits OrderDetails()
            foreach ($cart->getFull() as $product){
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);

                $this->entityManager->persist($orderDetails);
            }

            $this->entityManager->flush();

            return $this->render('order/add.html.twig', [
                'cart' => $cart->getFull(),
                'carrier' => $carriers,
                'delivery' => $delivery_content,
                'reference' => $order->getReference()
            ]);
        }
        
        return $this->redirectToRoute('app_cart');
    }
}
