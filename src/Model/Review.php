<?php

namespace App\Models;

use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Cette classe représente une commentaire dans la BDD.
 * Elle contient seulement la validation des données.
 */

class Review
{

    function __construct(
        private int|string|null $reviewId = null, // n'a pas de valeur au moment de l'instanciation
        private int $rideId,
        private int $authorId,
        private int $targetId,
        private int $rating,
        private string $comment,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $validatedAt = null
    ) {

        $this->setReviewRideId($rideId)
            ->setReviewAuthorId($authorId)
            ->setReviewTargetId($targetId)
            ->setReviewRating($rating)
            ->setReviewComment($comment);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }
    // ---------Les Getters ---------
    public function getReviewId(): int|string|null
    {
        return $this->reviewId;
    }

    public function getReviewRideId(): int
    {
        return $this->rideId;
    }

    public function getReviewAuthorId(): int
    {
        return $this->authorId;
    }

    public function getReviewTargetId(): int
    {
        return $this->targetId;
    }

    public function getReviewRating(): int
    {
        return $this->rating;
    }

    public function getReviewComment(): string
    {
        return $this->comment;
    }

    public function getReviewCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReviewValidatedAt(): DateTimeImmutable
    {
        return $this->validatedAt;
    }


    // ---------Les Setters ---------

    public function setReviewRideId(int $rideId): self
    {
        $this->rideId = $rideId;
        return $this;
    }

    public function setReviewAuthorId(int $authorId): self
    {
        $this->authorId = $authorId;
        return $this;
    }

    public function setReviewTargetId(int $targetId): self
    {
        $this->targetId = $targetId;
        return $this;
    }

    public function setReviewRating(int $rating): self
    {
        if ($rating < 0 || $rating > 5) {
            throw new InvalidArgumentException("La note doit être comprise entre 0 et 5.");
        }
        $this->rating = $rating;
        return $this;
    }

    public function setReviewComment(string $comment): self
    {
        $commentRegex = '/^[a-zA-ZÀ-ÿ0-9\s\'".,;:!?()@$%&-]{0,255}+$/u';
        $comment = trim($comment);

        if (!preg_match($commentRegex, $comment)) {
            throw new InvalidArgumentException("Le commentaire peut contenir jusqu'à 255 caractères autorisés.");
        }
        $this->comment = trim($comment);
        return $this;
    }

    public function setReviwValidatedAt(DateTimeImmutable $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }
}
