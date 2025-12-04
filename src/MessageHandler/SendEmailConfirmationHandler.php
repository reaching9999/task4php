<?php

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\SendEmailConfirmation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[AsMessageHandler]
class SendEmailConfirmationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private VerifyEmailHelperInterface $verifyEmailHelper
    ) {
    }

    public function __invoke(SendEmailConfirmation $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($message->getUserId());

        if (!$user) {
            return;
        }

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $email = (new TemplatedEmail())
            ->from(new Address('mailer@your-domain.com', 'Task 4 Bot'))
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
            ]);

        $this->mailer->send($email);
    }
}
