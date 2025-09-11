<?php

namespace App\DTO;

use App\Enum\ReviewStatus;
use InvalidArgumentException;

class CreateReviewDTO
{
    public ?int $rating;
    public ?string $comment;
    public ?ReviewStatus $reviewStatus;

    public function __construct(array $data)
    {
        $this->rating = isset($data['rating']) ? (int)($data['rating']) : null;

        $this->comment = isset($data['comment']) ? trim($data['comment']) : null;

        $reviewStatus = ReviewStatus::tryFrom($data['review_status'] ?? '');
        if ($reviewStatus === null) {
            throw new InvalidArgumentException("Statut invalide.");
        }
        $this->reviewStatus = $reviewStatus;
    }
}
