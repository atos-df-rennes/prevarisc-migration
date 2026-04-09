<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Groupement;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Repository\UtilisateurInformationsRepository;
use App\Security\Voter\DossierVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class ContactController extends AbstractController
{
    /**
     * @var UtilisateurInformationsRepository
     */
    private $utilisateurInformationsRepository;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        UtilisateurInformationsRepository $utilisateurInformationsRepository,
        RouterInterface $router
    ) {
        $this->utilisateurInformationsRepository = $utilisateurInformationsRepository;
        $this->router = $router;
    }

    /**
     * @Route("/contact/{id}/modifier", name="contact_edit", options={"expose"=true})
     */
    public function edit(Request $request, UtilisateurInformations $contact, EntityManagerInterface $entityManager): Response
    {
        $elementLie = $request->query->get('elementLie');
        $elementId = $request->query->getInt('elementId');

        switch ($elementLie) {
            case Contact::ETABLISSEMENT:
                $entity = $entityManager->find(Etablissement::class, $elementId);
                if (null === $entity) {
                    throw $this->createNotFoundException('Établissement non trouvé.');
                }

                $this->denyAccessUnlessGranted(EtablissementVoter::MODIFICATION, $entity);
                $redirectRoute = $this->router->generate('etablissement_contacts', ['id' => $elementId]);

                break;
            case Contact::GROUPEMENT:
                $entity = $entityManager->find(Groupement::class, $elementId);
                if (null === $entity) {
                    throw $this->createNotFoundException('Groupement non trouvé.');
                }

                $this->denyAccessUnlessGranted('groupement_communes');
                $redirectRoute = $this->router->generate('admin_groupement', ['id' => $elementId]);

                break;
            case Contact::DOSSIER:
                $entity = $entityManager->find(Dossier::class, $elementId);
                if (null === $entity) {
                    throw $this->createNotFoundException('Dossier non trouvé.');
                }

                /* @var Dossier $entity */
                $this->denyAccessUnlessGranted(DossierVoter::MODIFICATION, $entity);
                $redirectRoute = $this->router->generate('dossier_contacts', ['id' => $elementId]);

                if ($entity->hasVerrou()) {
                    $this->addFlash('warning', 'Le dossier est verrouillé.');

                    return $this->redirect($redirectRoute);
                }

                break;
            case Contact::COMMISSION:
                $entity = $entityManager->find(Commission::class, $elementId);
                if (null === $entity) {
                    throw $this->createNotFoundException('Commission non trouvée.');
                }

                $this->denyAccessUnlessGranted('gestion_commissions');
                $redirectRoute = $this->router->generate('admin_commissions_contacts', ['id' => $elementId]);

                break;
            default:
                throw $this->createNotFoundException("L'entité liée n'est pas reconnue.");
        }

        if (!$entity->getContacts()->contains($contact)) {
            throw $this->createNotFoundException("Ce contact n'appartient pas à cette entité.");
        }

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('contact_edit', [
                'id' => $contact->getId(),
                'elementLie' => $elementLie,
                'elementId' => $elementId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été modifié.');

            return $this->redirect($redirectRoute);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    /**
     * @Route("/contacts/{search}", name="contact_autocomplete", options={"expose"=true})
     */
    public function autocomplete(string $search): JsonResponse
    {
        return $this->json($this->utilisateurInformationsRepository->findByNameNotInUser($search));
    }
}
