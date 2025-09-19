<?php

namespace App\Service;

use App\Repository\RideRepository;
use App\Repository\BookingRepository;
use App\Enum\BookingStatus;
use DateTimeInterface;
use DateTimeImmutable;

class StaffService
{

    public function __construct(
        protected RideRepository $rideRepository,
        protected BookingRepository $bookingRepository,
        protected UserService $userService,
    ) {}

    // --------------TRAJETS ----------------------

    //------- Pour le staff uniquement ---------
    /**
     * Permet à un membre du personnel de récupèrer la liste des objets Booking par date de création.
     *
     * @param DateTimeInterface $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listRidesByCreationDate(
        DateTimeInterface $creationDate,
        int $staffId
    ): array {

        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->rideRepository->findAllRidesByCreationDate($creationDate);
    }


    //-------------Pour les Admins------------------
    /**
     * Permet à un admin de récupèrer le nombre de trajets effectués pour le jour J.
     *
     * @param integer $adminId
     * @return array
     */
    public function getNumberOfRidesFromToday(
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countRidesByToday();
    }

    /**
     * Permet à un admin de récupèrer le nombre de trajets effectués sur une période donnée.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param integer $adminId
     * @return array
     */
    public function getNumberOfRidesOverPeriod(
        DateTimeInterface $start,
        DateTimeInterface $end,
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countRidesByPeriod($start, $end);
    }

    /**
     * Permet à un admin de récupèrer le total des commissions gagnées.
     *
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommission(
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByFields([]);
    }

    /**
     * Permet à un admin de récupèrer le total des commissions gagnées pour le jour J.
     *
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommissionFromToday(
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByToday();
    }

    /**
     * Permet à un admin de récupèrer le total des commissions gagnées sur une période donnée.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommissionOverPeriod(
        DateTimeInterface $start,
        DateTimeInterface $end,
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByPeriod($start, $end);
    }



    //--------------RESERVATIONS--------------------

    //------- Pour le staff uniquement ---------

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon la date départ.
     *
     * @param DateTimeImmutable $departureDate
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByDepartureDate(
        DateTimeImmutable $departureDate,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->bookingRepository->findAllBookingsByDepartureDate($departureDate);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon le statut de réservation.
     *
     * @param BookingStatus $bookingStatus
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByStatus(
        BookingStatus $bookingStatus,
        int $staffId
    ): array {

        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->bookingRepository->fetchAllBookingsByStatus($bookingStatus);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon la date de création.
     *
     * @param DateTimeInterface $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByCreatedAt(
        DateTimeInterface $creationDate,
        int $staffId
    ): array {

        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->bookingRepository->fetchAllBookingsByCreatedAt($creationDate);
    }
}
