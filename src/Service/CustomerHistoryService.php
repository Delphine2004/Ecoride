<?php

namespace App\Service;

use App\Repository\RideRepository;
use App\Repository\BookingRepository;
use App\Service\UserService;
use InvalidArgumentException;


class CustomerHistoryService
{

    public function __construct(
        protected RideRepository $rideRepository,
        protected BookingRepository $bookingRepository,
        protected UserService $userService,
    ) {}


    //-------------Pour les conducteurs------------------


    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de récupèrer la liste d'objet Ride à venir d'un utilisateur CONDUCTEUR.
     *
     * @param integer $driverId
     * @param integer $userId
     * @return array
     */
    public function listUpcomingRidesByDriver(
        int $driverId,
        int $userId
    ): array {

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        $this->userService->checkIfUserExists($driverId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->findUpcomingRidesByDriver($driverId);
    }

    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de récupèrer la liste brute des trajets passés d'un utilisateur CONDUCTEUR.
     *
     * @param integer $driverId
     * @param integer $userId
     * @return array
     */
    public function listPastRidesByDriver(
        int $driverId,
        int $userId
    ): array {

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        $this->userService->checkIfUserExists($driverId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->fetchPastRidesByDriver($driverId);
    }


    //-------------Pour les Passagers------------------


    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste d'objet Ride à venir d'un utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listUpcomingRidesByPassenger(
        int $passengerId,
        int $userId
    ): array {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensurePassengerAndStaff($userId);

        $this->userService->checkIfUserExists($passengerId);

        // Vérification qu'il s'agit bien du passager
        if ($this->userService->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->findUpcomingRidesByPassenger($passengerId);
    }

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste brute des trajets passés d'un utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listPastRidesByPassenger(
        int $passengerId,
        int $userId
    ): array {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensurePassengerAndStaff($userId);

        $this->userService->checkIfUserExists($passengerId);

        // Vérification qu'il s'agit bien du passager
        if ($this->userService->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->fetchPastRidesByPassenger($passengerId);
    }

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste d'objet Booking à vénir d'une utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listUpcomingBookingsByPassenger(
        int $passengerId,
        int $userId
    ): array {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensurePassengerAndStaff($userId);

        $this->userService->checkIfUserExists($passengerId);

        // Vérification qu'il s'agit bien du passager
        if ($this->userService->isPassenger($passengerId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->bookingRepository->findUpcomingBookingsByPassenger($passengerId);
    }

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste brute des réservations passées d'une utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listPastBookingsByPassenger(
        int $passengerId,
        int $userId
    ): array {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensurePassengerAndStaff($userId);

        $this->userService->checkIfUserExists($passengerId);

        // Vérification qu'il s'agit bien du passager
        if ($this->userService->isPassenger($passengerId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->bookingRepository->fetchPastBookingsByPassenger($passengerId);
    }
}
