<?php

namespace App\Service;

use App\Repository\RideRepository;
use App\Repository\BookingRepository;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use DateTimeImmutable;

class StaffService
{

    public function __construct(
        protected RideRepository $rideRepository,
        protected BookingRepository $bookingRepository,
        protected UserService $userService,
    ) {}

    //------------- Pour le staff ----------------

    // --------------TRAJETS ----------------------

    /**
     * Permet à un membre du personnel de récupèrer la liste des trajets selon la date départ.
     *
     * @param DateTimeImmutable $departureDate
     * @param integer $staffId
     * @return array
     */
    public function listRidesByDepartureDate(
        DateTimeImmutable $departureDate,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->rideRepository->fetchAllBookingsByDepartureDate($departureDate);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des objets Ride par date de création.
     *
     * @param DateTimeImmutable $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listRidesByCreationDate(
        DateTimeImmutable $creationDate,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->rideRepository->fetchAllRidesByCreatedAt($creationDate);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des trajets selon le statut du trajet.
     *
     * @param RideStatus $rideStatus
     * @param integer $staffId
     * @return array
     */
    public function listRidesByRideStatus(
        RideStatus $rideStatus,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);
        return $this->rideRepository->findAllRidesByStatus($rideStatus);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des trajets selon le statut ENCOURS.
     *
     * @param RideStatus $rideStatus
     * @param integer $staffId
     * @return array
     */
    public function listRidesByPendingStatus(
        RideStatus $rideStatus,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->rideRepository->findAllRidesByStatus($rideStatus);
    }

    //--------------RESERVATIONS--------------------

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon le statut de réservation.
     *
     * @param BookingStatus $bookingStatus
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByBookingStatus(
        BookingStatus $bookingStatus,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->bookingRepository->findAllBookingsByStatus($bookingStatus);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations ENCOURS
     *
     * @param BookingStatus $bookingStatus
     * @param integer $staffId
     * @return array
     */
    public function listBookingByPendingStatus(
        BookingStatus $bookingStatus,
        int $staffId
    ): array {
        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->bookingRepository->findAllBookingsByStatus($bookingStatus);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon la date de création.
     *
     * @param DateTimeImmutable $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByCreatedAt(
        DateTimeImmutable $creationDate,
        int $staffId
    ): array {

        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->bookingRepository->fetchAllBookingsByCreatedAt($creationDate);
    }


    //-------------Pour les Admins uniquement ------------------
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
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @param integer $adminId
     * @return array
     */
    public function getNumberOfRidesOverPeriod(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
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
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommissionOverPeriod(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByPeriod($start, $end);
    }
}
