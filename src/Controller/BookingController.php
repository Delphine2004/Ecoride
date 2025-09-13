<?php

namespace App\Controllers;

use App\Services\BookingService;
use InvalidArgumentException;

class BookingController extends BaseController
{
    public function __construct(
        private BookingService $bookingService
    ) {}


    // POST
    public function createBooking(
        string $jwtToken
    ): void {}

    // PUT
    public function cancelBooking(
        string $jwtToken
    ): void {}

    // DELETE - pas d'annulation car conservation de l'historique des réservations

    // GET
    public function getBookingWithRideAndUsers(
        string $jwtToken
    ): void {}

    public function getBookingListByDate(
        string $jwtToken
    ): void {}
}
