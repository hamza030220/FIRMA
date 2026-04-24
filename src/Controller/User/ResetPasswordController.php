<?php

namespace App\Controller\User;

use App\Entity\User\PasswordResetToken;
use App\Repository\User\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1 — Enter email
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UtilisateurRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $sent = false;

        if ($request->isMethod('POST')) {
            $email       = trim($request->request->get('email', ''));
            $utilisateur = $repo->findOneBy(['email' => $email]);

            // Always show "sent" message even if email unknown (prevents email enumeration)
            if ($utilisateur && !$utilisateur->isBanned()) {
                // Invalidate old tokens for this user
                $old = $em->getRepository(PasswordResetToken::class)
                    ->findBy(['utilisateur' => $utilisateur]);
                foreach ($old as $t) { $em->remove($t); }

                $rawToken = bin2hex(random_bytes(32)); // 64 hex chars
                $expires  = new \DateTime('+1 hour');
                $token    = new PasswordResetToken($utilisateur, $rawToken, $expires);
                $em->persist($token);
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $rawToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $mail = (new Email())
                    ->from('no-reply@firma.tn')
                    ->to($utilisateur->getEmail())
                    ->subject('Réinitialisation de votre mot de passe — FIRMA')
                    ->html($this->renderView('user/reset_password/email.html.twig', [
                        'utilisateur' => $utilisateur,
                        'resetUrl'    => $resetUrl,
                        'expires'     => $expires,
                    ]));

                $mailer->send($mail);
            }

            $sent = true;
        }

        return $this->render('user/reset_password/forgot.html.twig', ['sent' => $sent]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2 — Enter new password
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/reinitialiser/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        /** @var PasswordResetToken|null $resetToken */
        $resetToken = $em->getRepository(PasswordResetToken::class)
            ->findOneBy(['token' => $token, 'used' => false]);

        if (!$resetToken || $resetToken->isExpired()) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password        = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');

            if (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                $utilisateur = $resetToken->getUtilisateur();
                $hashed      = $hasher->hashPassword($utilisateur, $password);
                $utilisateur->setMotDePasse($hashed);
                $resetToken->markUsed();
                $em->flush();

                $this->addFlash('success', 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('user/reset_password/reset.html.twig', [
            'token' => $token,
            'error' => $error,
        ]);
    }
}