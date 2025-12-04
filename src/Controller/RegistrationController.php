<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Message\SendEmailConfirmation;
use Symfony\Component\Messenger\MessageBusInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $req, 
        UserPasswordHasherInterface $hasher, 
        EntityManagerInterface $em,
        MessageBusInterface $bus
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_index');
        }

        $u = new User();
        $form = $this->createForm(RegistrationFormType::class, $u);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $u->setPassword(
                $hasher->hashPassword(
                    $u,
                    $form->get('plainPassword')->getData()
                )
            );

            $em->persist($u);
            $em->flush();

            // Dispatch async email message here
            $bus->dispatch(new SendEmailConfirmation($u->getId()));

            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

            // TEMPORARY: Commented out for demo video so you can see the Mailer Toolbar
            // return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $req, 
        UserRepository $repo, 
        VerifyEmailHelperInterface $helper,
        EntityManagerInterface $em
    ): Response
    {
        $id = $req->query->get('id');
        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $u = $repo->find($id);
        if (null === $u) {
            return $this->redirectToRoute('app_register');
        }

        try {
            $helper->validateEmailConfirmation(
                $req->getUri(),
                (string) $u->getId(),
                $u->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('verify_email_error', $e->getReason());
            return $this->redirectToRoute('app_register');
        }

        $u->setStatus(User::STATUS_ACTIVE);
        $em->flush();

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_login');
    }
}
