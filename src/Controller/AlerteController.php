<?php

namespace App\Controller;

use App\Repository\ChangementRepository;
use App\Repository\EtablissementRepository;
use App\Service\Alerte\AlerteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller pour la gestion des alertes email.
 *
 * Équivalent legacy : ChangementController (alerteformAction)
 */
class AlerteController extends AbstractController
{
    /**
     * Affiche le formulaire d'alerte email (modale).
     *
     * Équivalent legacy : ChangementController::alerteformAction()
     * Route legacy : /changement/alerteform
     *
     * @Route("/symfony/alerte/formulaire", name="alerte_formulaire", methods={"GET"})
     */
    public function formulaire(
        Request $request,
        EtablissementRepository $etablissementRepository,
        ChangementRepository $changementRepository,
        AlerteService $alerteService
    ): Response {
        $etablissementId = $request->query->getInt('etablissement');
        $typeChangement = $request->query->getInt('type', 2); // 2 = avis

        if (!$etablissementId) {
            throw $this->createNotFoundException('ID établissement requis');
        }

        // Récupérer l'établissement
        $etablissement = $etablissementRepository->find($etablissementId);
        if (!$etablissement) {
            throw $this->createNotFoundException(sprintf('Établissement #%d introuvable', $etablissementId));
        }

        // Récupérer le template de changement
        $changement = $changementRepository->find($typeChangement);
        if (!$changement) {
            throw $this->createNotFoundException(sprintf('Type changement #%d introuvable', $typeChangement));
        }

        // Récupérer les destinataires
        $destinataires = $alerteService->getDestinataires($typeChangement, $etablissement);

        // Formater pour le template
        $emails = [];
        foreach ($destinataires as $destinataire) {
            $emails[] = $destinataire['mail'];
        }

        // Générer objet email
        $objet = $alerteService->getObjet($typeChangement, $etablissement);

        // Générer message email avec remplacement balises
        $messageTemplate = $changement->getMessage() ?? '';
        $message = $alerteService->getMessage($messageTemplate, $etablissement);

        return $this->render('alerte/formulaire.html.twig', [
            'etablissementId' => $etablissementId,
            'typeChangement' => $typeChangement,
            'destinataires' => $destinataires,
            'emails' => implode(';', $emails),
            'objet' => $objet,
            'message' => $message,
        ]);
    }

    /**
     * Envoie l'email d'alerte.
     *
     * Équivalent legacy : ChangementController::sendmailalerteAction()
     * Route legacy : /changement/sendmailalerte
     *
     * @Route("/symfony/alerte/envoyer", name="alerte_envoyer", methods={"POST"})
     *
     * @param Request         $request Requête HTTP
     * @param MailerInterface $mailer  Service Symfony Mailer (injecté)
     */
    public function envoyer(Request $request, MailerInterface $mailer): Response
    {
        $emailsRaw = $request->request->get('emails');
        $objetRaw = $request->request->get('objet');
        $messageRaw = $request->request->get('message');

        // Validation types (strict)
        if (!is_string($emailsRaw)) {
            throw new \InvalidArgumentException('Le paramètre emails doit être une chaîne');
        }

        if (!is_string($objetRaw)) {
            throw new \InvalidArgumentException('Le paramètre objet doit être une chaîne');
        }

        if (!is_string($messageRaw)) {
            throw new \InvalidArgumentException('Le paramètre message doit être une chaîne');
        }

        // Validation valeurs
        if ('' === $emailsRaw) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun destinataire spécifié',
            ]);
        }

        if ('' === $objetRaw || '' === $messageRaw) {
            return $this->json([
                'success' => false,
                'message' => 'Objet et message requis',
            ]);
        }

        // Extraction destinataires
        $destinataires = array_filter(array_map('trim', explode(';', $emailsRaw)));
        if ([] === $destinataires) {
            return $this->json([
                'success' => false,
                'message' => 'Format emails invalide',
            ]);
        }

        // Récupération expéditeur depuis config
        $fromEnv = $_ENV['MAILER_FROM'] ?? null;
        $from = is_string($fromEnv) ? $fromEnv : 'noreply@prevarisc.local';

        try {
            // Création email avec nouveau composant Symfony Mailer
            $email = new Email();
            $email->from($from);
            $email->subject($objetRaw);
            $email->html($messageRaw);

            // Ajout destinataires en BCC (comme legacy)
            foreach ($destinataires as $destinataire) {
                $email->addBcc($destinataire);
            }

            // Envoi
            $mailer->send($email);

            return $this->json([
                'success' => true,
                'message' => sprintf('Email envoyé avec succès à %d destinataire(s)', count($destinataires)),
            ]);
        } catch (TransportExceptionInterface $transportException) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur : '.$transportException->getMessage(),
            ]);
        }
    }
}
