<?php

namespace App\Repository;

use App\Model\Review;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;


class ReviewRepository
{

    private Collection $collection;

    public function __construct(Client $mongoClient, string $databaseName = 'ecoride')
    {
        $this->collection = $mongoClient->selectCollection($databaseName, 'reviews');
    }

    public function findReview(string $reviewId): ?Review
    {
        $document = $this->collection->findOne(['_id' => new ObjectId($reviewId)]);

        if (!$document) {
            return null;
        }
        return Review::fromDatabaseRow($document->getArrayCopy());
    }

    public function insertReview(Review $review): Review
    {
        $document = [
            'rideId' => $review->getReviewRideId(),
            'authorId' => $review->getReviewAuthorId(),
            'targetId' => $review->getReviewTargetId(),
            'rating' => $review->getReviewRating(),
            'comment' => $review->getReviewComment(),
            'reviewStatus' => $review->getReviewStatus()->value,
            'createdAt' => new UTCDateTime($review->getReviewCreatedAt()->getTimestamp() * 1000),
            'validatedAt' => $review->getReviewValidatedAt()
                ? new UTCDateTime($review->getReviewValidatedAt()->getTimestamp() * 1000)
                : null
        ];
        $result = $this->collection->insertOne($document);
        $review->setReviewId(
            (string) $result->getInsertedId()
        );
        return $review;
    }


    public function updateReview(Review $review): bool
    {
        $document = [
            'reviewStatus' => $review->getReviewStatus()->value,
            'validatedAt' => $review->getReviewValidatedAt()
                ? new UTCDateTime($review->getReviewValidatedAt()->getTimestamp() * 1000)
                : null
        ];

        $result = $this->collection->updateOne(
            ['_id' => new ObjectId($review->getReviewId())],
            ['$set' => $document]
        );

        return $result->getModifiedCount() > 0;
    }
}
