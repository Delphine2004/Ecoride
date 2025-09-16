<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\RideRepository;
use App\Repository\ReviewRepository;
use App\Model\Ride;
use App\Model\Review;
use App\Enum\ReviewStatus;
use InvalidArgumentException;

class ReviewService extends BaseService
{

    public function __construct(

        private UserRepository $userRepository,
        private RideRepository $rideRepository,
        private ReviewRepository $reviewRepository
    ) {}


    // Permet à un utilisateur PASSAGER de laisser un commentaire à un utilisateur CONDUCTEUR.
    public function addReviewToDriver(
        Ride $ride,
        int $passengerId,
        array $data
    ): ?Review {

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }


        // Récupération de l'utilisateur
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification de l'existence de l'utilisateur
        if (!$passenger) {
            throw new InvalidArgumentException(".");
        }

        // Vérification de la permission
        $this->ensureCustomer($passengerId);


        // Récupération de l'id du chauffeur
        $driverId = $ride->getRideDriverId();

        // Récupération du conducteur
        $driver = $ride->getRideDriver();

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification que le passager n'est pas le conducteur
        if ($passengerId === $driverId) {
            throw new InvalidArgumentException("Le passager ne peut pas être le conducteur.");
        }


        // Récupération des passagers
        $ridePassengers = $ride->getRidePassengers();

        // Vérification que le passager a bien participé au trajet
        if (!in_array($passenger, $ridePassengers, true)) {
            throw new InvalidArgumentException("Le passager n'a pas participé à ce trajet.");
        }



        // Création de l'objet Review
        $review = new Review();

        // Remplissage de l'objet
        $review->setReviewAuthorId($passengerId);
        $review->setReviewTargetId($driverId);
        $review->setReviewRating($data['rating']);
        if (!empty($data['comment'])) {
            $review->setReviewComment($data['comment']);
        }
        $review->setReviewStatus(ReviewStatus::ATTENTE);

        //$this->reviewRepository->insertReview($review);


        return $review;
    }



    //----------Actions EMPLOYEE ------------

    //Fonction qui permet à un employé d'approuver un commentaire.
    public function approveReview(
        Review $review,
        int $staffMemberId,
    ): ?Review {
        // Vérification de l'existance du commentaire
        if (!$review) {
            throw new InvalidArgumentException("Commentaire introuvable.");
        }

        // Vérification du statut du commentaire
        if ($review->getReviewStatus() !== ReviewStatus::ATTENTE) {
            throw new InvalidArgumentException("Le commentaire est déjà approuvé.");
        }

        // Récupération de l'employé
        $staffMember = $this->userRepository->findUserById($staffMemberId);

        // Vérification de l'existence de l'employé
        if (!$staffMember) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification de la permission
        $this->ensureStaff($staffMemberId);

        // Modifier le statut
        $review->setReviewStatus(ReviewStatus::CONFIRME);

        //$this->reviewRepository->updateReview($review);
        return $review;
    }


    public function rejectReview(
        Review $review,
        int $staffMemberId,
    ): ?Review {
        // Vérification de l'existance du commentaire
        if (!$review) {
            throw new InvalidArgumentException("Commentaire introuvable.");
        }

        // Vérification du statut du commentaire
        if ($review->getReviewStatus() !== ReviewStatus::ATTENTE) {
            throw new InvalidArgumentException("Le commentaire est déjà approuvé.");
        }

        // Récupération de l'employé
        $staffMember = $this->userRepository->findUserById($staffMemberId);

        // Vérification de l'existence de l'employé
        if (!$staffMember) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification de la permission
        $this->ensureStaff($staffMemberId);

        // Modifier le statut
        $review->setReviewStatus(ReviewStatus::REJETE);

        //$this->reviewRepository->updateReview($review);
        return $review;
    }
}
