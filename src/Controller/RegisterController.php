<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use App\Classe\Mail;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }


    #[Route('/inscription', name: 'app_register')]
    public function index(EntityManagerInterface $entityManager, Request $request, PersistenceManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {

        $notification = null;

        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData(); 

            //vérifier que l'utilisateur n'est pas déja enregistré
            $search_email = $this->entityManager->getRepository(User::class)->findOneByEmail($user->getEmail());

            if(!$search_email){

                // Encodage du mot de passe
                $password = $passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($password);

                // Enregistrer dans la base de données
                $em = $doctrine->getManager();
                $em->persist($user);
                $em->flush();

                // Envoie du mail de confirmation d'inscription
                $mail = new Mail();
                $content = "Bonjour ".$user->getFirstname()."<br/>Bienvenue sur la première boutique dédiée au made in France. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.";
                $mail->send($user->getEmail(), $user->getFirstname(), 'Bienvenue sur La Boutique Française', $content);
                $notification = "Votre inscription s'est correctement déroulée. Vous pouvez dès à présent vous connecter à votre compte.";

            } else {
                // Envoie d'une notification
                $notification = "L'email que vous avez rensigné existe déjà.";
            }

        }

        return $this->render('register/index.html.twig',[
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
