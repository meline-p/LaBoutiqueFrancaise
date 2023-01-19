<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;


class ResetPasswordController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/mot-de-passe-oublie', name: 'app_reset_password')]
    public function index(Request $request): Response
    {
        if($this->getUser())
        {
            return $this->redirectToRoute("app_home");
        }

        if($request->get('email')){
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));
            
            if($user){
                // Etape 1 : Enregistrer en base la demande de reset_password avec user, token, createdAt
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                // Etape 2 : Envoyer un mail à l'utilisateur avec un lien lui permettant de mettre a jour son mot de passe
                $url = $this->generateUrl('app_update_password', [
                    'token' => $reset_password->getToken()
                ]);
               
                $content = "Bonjour ".$user->getFirstname().",<br/>Vous avez demandé à réinitialiser votre mot de passe sur La Boutique Française.<br/><br/>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href=".$url.">mettre à jour votre mot de passe</a>.";

                $mail = new Mail();
                $mail->send($user->getEmail(), $user->getFirstname().' '.$user->getLastname(), 'Réinitialiser votre mot de passe sur La Boutique Française.', $content);
           
                $this->addFlash('notice', 'Vous allez recevoir un mail dans quelques secondes avec la procédure pour réinitialiser votre mot de passe.');

            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }


    #[Route('/modifier-mon-mot-de-passe/{token}', name: 'app_update_password')]
    public function update(Request $request, $token,PersistenceManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if (!$reset_password) {
            return $this->redirectToRoute("app_reset_password");
        }

        // Vérifier si le createdAt est de moins de 3h
        $now = new \DateTime();
        if($now > $reset_password->getCreatedAt()->modify('+ 3 hour')){
            $this->addFlash('notice', 'Votre demande de mot de passe a expirée. Merci de la renouveler.');
            return $this->redirectToRoute('app_reset_password');
        } 


        // Rendre une vue avec mot de passe et confirmer votre mot de passe
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $new_pwd = $form->get('new_password')->getData();

            // Encodage des mots de passe
            $password = $passwordHasher->hashPassword($reset_password->getUser(), $new_pwd);
            $reset_password->getUser()->setPassword($password);

            // Flush en base de données
            $this->entityManager->flush();

            // Redirection de l'utilisateur vers la page de connexion
            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig', [
            'form' => $form
        ]);
    }
}