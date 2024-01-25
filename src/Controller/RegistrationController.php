<?php

namespace App\Controller;

use App\Service\UserInfoService;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\CustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
class RegistrationController extends AbstractController
{

    private $userInfoService;
    public function __construct(UserInfoService $userInfoService) {
        $this->userInfoService = $userInfoService;
    }


    #[Route('/register', name: 'app_register')]
    public function register(AuthenticationUtils $authenticationUtils,Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, CustomAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            $user->setRoles(['ROLE_ADMIN']);
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }
        $userInfo = $this->userInfoService->getUserInfo();
        $lastUsername = $authenticationUtils->getLastUsername();
        $user = $userInfo['user'];
        $isAdmin = $userInfo['isAdmin'];
        $isLoggedIn = $userInfo['isLoggedIn'];
        return $this->render('registration/register.html.twig', ['user' => $user,'isAdmin' => $isAdmin, 'isLoggedIn' => $isLoggedIn,
            'registrationForm' => $form->createView(),
        ]);
    }
}