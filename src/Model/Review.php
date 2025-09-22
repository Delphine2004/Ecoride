<?php

namespace App\Model;

use App\Enum\ReviewStatus;
use App\Utils\RegexPatterns;
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
        private ?int $rideId = null,
        private ?int $authorId = null,
        private ?int $targetId = null,
        private ?int $rating = null,
        private ?string $comment = null,
        private ?ReviewStatus $reviewStatus = null,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $validatedAt = null
    ) {

        $this->setReviewRideId($rideId)
            ->setReviewAuthorId($authorId)
            ->setReviewTargetId($targetId)
            ->setReviewRating($rating)
            ->setReviewComment($comment)
            ->setReviewStatus($reviewStatus);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }


    public static function fromDatabaseRow(array $row): self
    {
        $reviewId =  (string)$row['review_id'] ?? null;
        $rideId = (string) $row['ride_id'] ?? null;
        $authorId = (string) $row['author_id'] ?? null;
        $targetId =  (string) $row['target_id'] ?? null;
        $rating =  (int) $row['rating'] ?? null;
        $comment =  (string)$row['comment'] ?? null;
        $reviewStatus =  ReviewStatus::tryFrom($row['review_status']) ?? null;
        $createdAt = !empty($row['created_at']) ? new DateTimeImmutable($row['created_at']) : null;
        $validatedAt = !empty($row['validated_at']) ? new DateTimeImmutable($row['validated_at']) : null;

        return new self(
            reviewId: $reviewId,
            rideId: $rideId,
            authorId: $authorId,
            targetId: $targetId,
            rating: $rating,
            comment: $comment,
            reviewStatus: $reviewStatus,
            createdAt: $createdAt,
            validatedAt: $validatedAt,
        );
    }




    // ---------Les Getters ---------
    public function getReviewId(): int|string|null
    {
        return $this->reviewId;
    }

    public function getReviewRideId(): ?int
    {
        return $this->rideId;
    }

    public function getReviewAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function getReviewTargetId(): ?int
    {
        return $this->targetId;
    }

    public function getReviewRating(): ?int
    {
        return $this->rating;
    }

    public function getReviewComment(): ?string
    {
        return $this->comment;
    }

    public function getReviewStatus(): ?ReviewStatus
    {
        return $this->reviewStatus;
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

    public function setReviewId(string $reviewId): self
    {
        $this->reviewId = $reviewId;
        return $this;
    }

    public function setReviewRideId(?int $rideId): self
    {
        $this->rideId = $rideId;
        return $this;
    }

    public function setReviewAuthorId(?int $authorId): self
    {
        $this->authorId = $authorId;
        return $this;
    }

    public function setReviewTargetId(?int $targetId): self
    {
        $this->targetId = $targetId;
        return $this;
    }

    public function setReviewRating(?int $rating): self
    {
        if ($rating < 0 || $rating > 5) {
            throw new InvalidArgumentException("La note doit être comprise entre 0 et 5.");
        }
        $this->rating = $rating;
        return $this;
    }

    public function setReviewComment(?string $comment): self
    {

        if ($comment !== null) {
            $comment = trim($comment);


            if (!preg_match(RegexPatterns::COMMENT_REGEX, $comment)) {
                throw new InvalidArgumentException("Le commentaire peut contenir entre 2 et 255 caractères autorisés.");
            }
        }

        $this->comment = ucfirst($comment);
        return $this;
    }

    public function setReviewStatus(?ReviewStatus $reviewStatus): self
    {
        $this->reviewStatus = $reviewStatus;
        return $this;
    }

    public function setReviewValidatedAt(DateTimeImmutable $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }
}
