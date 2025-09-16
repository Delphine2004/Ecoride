<?php

namespace App\Controller;

use App\DTO\CreateCarDTO;
use App\Service\CarService;
use InvalidArgumentException;

class CarController extends BaseController
{
    public function __construct(
        private CarService $carService
    ) {}


    // ------------------------POST--------------------------------
    /**
     * Autorise un utilisateur à créer une voiture.
     *
     * @param string $jwtToken
     * @return void
     */
    public function createCar(
        string $jwtToken
    ): void {
        // Récupération des données
        $data = $this->getJsonBody();

        // Vérification de la validité des données reçues
        if (!is_array($data) || empty($data)) {
            $this->errorResponse("JSON invalide ou vide.", 400);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Conversion des données en DTO
            $dto = new CreateCarDTO($data);

            // Ajout de la voiture
            $car = $this->carService->createCar($dto, $userId);

            // Définir le header Location
            $carId = null;
            if (is_object($car) && method_exists($car, 'getCarId')) {
                $carId = $car->getCarId();
            } elseif (is_array($car)) {
                $carId = $car['id'] ?? $car['car_id'] ?? null;
            }
            // Envoi de la réponse positive
            $this->successResponse($car, 201, "/users/{$userId}/cars/{$carId}");
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // // ------------------------PUT--------------------------------
    // pas de modification de voiture possible


    // --------------------------DELETE------------------------------
    /**
     * Autorise un utilisateur à supprimer une voiture.
     *
     * @param integer $carId
     * @param string $jwtToken
     * @return void
     */
    public function deleteCar(
        int $carId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Suppression de la voiture
            $removed = $this->carService->removeCar($carId, $userId);

            // Vérification de l'existence de la voiture
            if ($removed) {
                $this->successResponse(["message" => "Voiture supprimée."]);
            } else {
                $this->errorResponse("Voiture introuvable.", 404);
            }
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // --------------------------GET----------------------------------
    /**
     * Autorise un utilisateur à récupèrer la liste des voitures d'un CONDUCTEUR.
     *
     * @param int $driverId
     * @param string $jwtToken
     * @return void
     */
    public function listCarsbyDriver(
        int $driverId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération de la liste de voiture
            $cars = $this->carService->listCarsByDriver($driverId, $userId);

            // Envoi de la réponse positive
            $this->successResponse($cars);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
