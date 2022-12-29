<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\ReservationState;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $sorters = ['resource' => 'desc', 'id' => 'asc'];
        $requested = $reservationRepository->findBy(['state' => ReservationState::Requested], $sorters);
        $approved = $reservationRepository->findBy(['state' => ReservationState::Approved], $sorters);
        $paid = $reservationRepository->findBy(['state' => ReservationState::Paid], $sorters);
        $canceled = $reservationRepository->findBy(['state' => ReservationState::Canceled], $sorters);
        $completed = $reservationRepository->findBy(['state' => ReservationState::Completed], $sorters);

        return $this->render('reservation/index.html.twig', [
            'requested' => $requested,
            'approved' => $approved,
            'paid' => $paid,
            'canceled' => $canceled,
            'completed' => $completed,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ReservationRepository $reservationRepository): Response
    {
        $reservation = new Reservation();

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservationRepository->save($reservation, true);

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/reservation/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/reservation/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Reservation $reservation,
        ReservationRepository $reservationRepository
    ): Response {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservationRepository->save($reservation, true);

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/reservation/{id}/delete', name: 'app_reservation_delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        Reservation $reservation,
        ReservationRepository $reservationRepository
    ): Response {
        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
                $reservationRepository->remove($reservation, true);
            }

            return $this->redirectToRoute(
                'app_resource_show',
                ['id' => $reservation->getResource()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('reservation/delete.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{id}/transit/{transition}', name: 'app_reservation_transit', methods: ['GET'])]
    public function transit(
        Request $request,
        string $transition,
        Reservation $reservation,
        WorkflowInterface $reservationStateMachine,
        EntityManagerInterface $em
    ) {
        try {
            $reservationStateMachine->apply($reservation, $transition);
            $em->flush();
        } catch (LogicException $exception) {
            $this->addFlash(
                'error',
                sprintf(
                    'Transition %s of %s workflow cannot be applied to %s',
                    $transition,
                    $reservationStateMachine->getName(),
                    $reservation->__toString()
                )
            );
        }
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_resource_show', ['id' => $reservation->getResource()->getId()]);
    }
}
