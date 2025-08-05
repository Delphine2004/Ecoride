<!DOCTYPE html>
<html lang="fr">

<head>
    <?php require_once "../src/view/partials/head.php" ?>
    <?php require_once "../src/view/partials/scripts.php" ?>
    <title>Dashboard EcoRide</title>
</head>

<body>
    <div class="sidebar">
        <h2>Admin</h2>
        <a href="#">Dashboard</a>
        <a href="#">Utilisateurs</a>
        <a href="#">Trajets</a>
        <a href="#">Réclamations</a>
        <a href="#">Déconnexion</a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Bienvenue administrateur</h1>
            <p>Gestion des utilisateurs</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Jean Martin</td>
                        <td>jean@example.com</td>
                        <td>admin</td>
                        <td>
                            <button class="btn btn-edit">Modifier</button>
                            <button class="btn btn-delete">Supprimer</button>
                        </td>
                    </tr>
                    <!-- Ajouter d'autres lignes dynamiquement avec PHP -->
                </tbody>
            </table>
        </div>
    </div>


</body>

</html>