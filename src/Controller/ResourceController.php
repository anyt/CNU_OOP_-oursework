<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\ReservationState;
use App\Entity\Resource;
use App\Form\ReservationType;
use App\Form\ResourceType;
use App\Repository\ReservationRepository;
use App\Repository\ResourceRepository;
use AppendIterator;
use DateInterval;
use DatePeriod;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_USER')]
class ResourceController extends AbstractController
{
    #[Route('/', name: 'app_resource_index', methods: ['GET'])]
    public function index(ResourceRepository $resourceRepository): Response
    {
        return $this->render('resource/index.html.twig', [
            'resources' => $resourceRepository->findAll(),
        ]);
    }

    #[Route('/resource/new', name: 'app_resource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ResourceRepository $resourceRepository): Response
    {
        $resource = new Resource();
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resourceRepository->save($resource, true);

            return $this->redirectToRoute('app_resource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('resource/new.html.twig', [
            'resource' => $resource,
            'form' => $form,
        ]);
    }

    #[Route('/resource/{id}', name: 'app_resource_show', methods: ['GET', 'POST'])]
    public function show(Resource $resource, Request $request, ReservationRepository $reservationRepository): Response
    {
        $reservation = new Reservation();
        $reservation->setState(ReservationState::Requested);
        $reservation->setResource($resource);
        $reservation->setClient($this->getUser());
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservationRepository->save($reservation, true);
        }

        $reservations = $reservationRepository->getActiveReservations($resource);

        $reservedDates = new AppendIterator();
        foreach ($reservations as $res) {
            $period = new DatePeriod(
                $res->getStartsAt(), new DateInterval('P1D'), $res->getEndsAt()->modify('+1 day')
            );
            $reservedDates->append($period->getIterator());
        }

        $reservedDates = array_map(fn(DateTime $date) => $date->format('m/d/Y'), iterator_to_array($reservedDates));

        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
            'reservation' => $reservation,
            'reservations' => $reservations,
            'form' => $form->createView(),
            'reservedDates' => $reservedDates,
        ]);
    }

    #[Route('/resource/{id}/edit', name: 'app_resource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Resource $resource, ResourceRepository $resourceRepository): Response
    {
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resourceRepository->save($resource, true);

            return $this->redirectToRoute('app_resource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('resource/edit.html.twig', [
            'resource' => $resource,
            'form' => $form,
        ]);
    }

    #[Route('/resource/{id}/delete', name: 'app_resource_delete', methods: ['POST'])]
    public function delete(Request $request, Resource $resource, ResourceRepository $resourceRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$resource->getId(), $request->request->get('_token'))) {
            $resourceRepository->remove($resource, true);
        }

        return $this->redirectToRoute('app_resource_index', [], Response::HTTP_SEE_OTHER);
    }
}
