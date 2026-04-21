<?php

namespace App\Service\Maladie\Weather;

use App\Entity\User\Utilisateur;
use App\Repository\User\UtilisateurRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MaladieWeatherAlertMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly string $weatherAlertSender,
        private readonly string $weatherAlertAdminFallback,
    ) {
    }

    /**
     * @param array<string, mixed> $weather
     * @param array<int, array<string, mixed>> $analyses
     */
    public function sendRiskAlert(Utilisateur $user, string $city, array $weather, array $analyses): void
    {
        if ($analyses === []) {
            return;
        }

        $recipients = $this->utilisateurRepository->findClientEmails();
        if ($recipients === [] && $this->weatherAlertAdminFallback !== '') {
            $recipients[] = $this->weatherAlertAdminFallback;
        }

        $recipients = array_values(array_unique(array_filter($recipients)));
        if ($recipients === []) {
            return;
        }

        $rows = '';
        $textRows = [];
        foreach ($analyses as $analysis) {
            $risk = $analysis['risk'] ?? [];
            $maladieName = htmlspecialchars((string) ($analysis['maladie'] ?? 'Maladie'), ENT_QUOTES, 'UTF-8');
            $riskMessage = htmlspecialchars((string) ($risk['message'] ?? 'Risque detecte'), ENT_QUOTES, 'UTF-8');
            $riskLabel = htmlspecialchars((string) ($risk['label'] ?? 'Alerte'), ENT_QUOTES, 'UTF-8');

            $rows .= sprintf(
                '<tr>'
                . '<td style="padding:14px 16px;border-top:1px solid #e6ece8;">'
                . '<div style="font-size:16px;font-weight:700;color:#173d2e;margin-bottom:4px;">%s</div>'
                . '<div style="font-size:14px;line-height:1.55;color:#50645b;margin-bottom:6px;">%s</div>'
                . '<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:#fff3e6;color:#9a5a00;font-size:12px;font-weight:700;">%s</span>'
                . '</td>'
                . '</tr>',
                $maladieName,
                $riskMessage,
                $riskLabel
            );

            $textRows[] = sprintf(
                '- %s: %s (%s)',
                trim(strip_tags($maladieName)),
                trim(strip_tags($riskMessage)),
                trim(strip_tags($riskLabel))
            );
        }

        $temperature = isset($weather['main']['temp']) ? (float) $weather['main']['temp'] : null;
        $humidity = isset($weather['main']['humidity']) ? (int) $weather['main']['humidity'] : null;
        $sender = $this->weatherAlertSender !== '' ? $this->weatherAlertSender : 'noreply@firma.tn';

        $html = sprintf(
            '<!DOCTYPE html>'
            . '<html lang="fr">'
            . '<body style="margin:0;padding:0;background:#f3f6f4;font-family:Arial,sans-serif;color:#1f3129;">'
            . '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="background:#f3f6f4;padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:20px;overflow:hidden;">'
            . '<tr>'
            . '<td style="padding:24px;background:linear-gradient(135deg,#113b2b 0%%,#1d6a4f 100%%);color:#ffffff;">'
            . '<div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.86;margin-bottom:8px;">FIRMA</div>'
            . '<div style="font-size:28px;font-weight:800;line-height:1.15;">Alerte meteo maladie</div>'
            . '<div style="margin-top:10px;font-size:15px;line-height:1.6;opacity:.92;">Une situation de risque a ete detectee pour la ville de <strong>%s</strong>.</div>'
            . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="padding:22px 20px 8px;">'
            . '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;">'
            . '<tr>'
            . '<td style="width:100%%;padding:14px 16px;background:#f8fbf9;border-radius:16px;">'
            . '<div style="font-size:12px;text-transform:uppercase;color:#6b8076;font-weight:700;">Conditions</div>'
            . '<div style="margin-top:6px;font-size:14px;color:#173d2e;"><strong>Temperature:</strong> %s</div>'
            . '<div style="margin-top:4px;font-size:14px;color:#173d2e;"><strong>Humidite:</strong> %s</div>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="padding:8px 20px 24px;">'
            . '<div style="font-size:16px;font-weight:700;color:#173d2e;margin-bottom:10px;">Maladies en alerte</div>'
            . '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="border:1px solid #e6ece8;border-radius:18px;overflow:hidden;background:#ffffff;">'
            . '%s'
            . '</table>'
            . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="padding:0 20px 24px;">'
            . '<div style="font-size:13px;line-height:1.7;color:#6a7d74;background:#f8fbf9;border-radius:16px;padding:14px 16px;">'
            . 'Email automatique envoye par FIRMA depuis <strong>Firmaagritech@gmail.com</strong>.'
            . '</div>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr></table>'
            . '</body></html>',
            htmlspecialchars($city, ENT_QUOTES, 'UTF-8'),
            $temperature !== null ? round($temperature, 1) . ' C' : 'N/A',
            $humidity !== null ? $humidity . '%' : 'N/A',
            $rows
        );

        $text = implode("\n", [
            'FIRMA - Alerte meteo maladie',
            '',
            'Ville : ' . $city,
            'Temperature : ' . ($temperature !== null ? round($temperature, 1) . ' C' : 'N/A'),
            'Humidite : ' . ($humidity !== null ? $humidity . '%' : 'N/A'),
            '',
            'Maladies en alerte :',
            implode("\n", $textRows),
        ]);

        $email = (new Email())
            ->from($sender)
            ->to($sender)
            ->bcc(...$recipients)
            ->subject('FIRMA - Alerte meteo maladie pour ' . $city)
            ->text($text)
            ->html($html);

        $this->mailer->send($email);
    }
}
