DROP DATABASE ecoride_db;

CREATE DATABASE IF NOT EXISTS ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ecoride_db;

# ---------Script MySQL------------

# Table: users
CREATE TABLE users(
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        last_name VARCHAR (50) NOT NULL,
        first_name VARCHAR (50) NOT NULL,
        email VARCHAR (150) NOT NULL UNIQUE,
        password VARCHAR (255) NOT NULL,
        login VARCHAR (50) NULL UNIQUE,
        phone VARCHAR (20) NULL,
        address VARCHAR (100) NULL,
        city VARCHAR (50) NULL,
        zip_code VARCHAR (10) NULL,
        picture VARCHAR (200) NULL,
        licence_no VARCHAR (50) NULL,
        credits INT UNSIGNED NULL DEFAULT 0,
        preferences JSON NULL,
        created_at TIMESTAMP NOT NULL,
        updated_at TIMESTAMP NOT NULL
);

# Table: roles
CREATE TABLE roles(
        role_id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(50) UNIQUE NOT NULL
);


# Table: user_roles
# Permet d'associer plusieurs rôles à un utilisateur
CREATE TABLE user_roles (
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        PRIMARY KEY(user_id, role_id),
        FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY(role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);


# Table: cars
CREATE TABLE cars(
        car_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_brand VARCHAR (100) NOT NULL ,
        car_model VARCHAR (100) NOT NULL ,
        car_color VARCHAR (50) NOT NULL ,
        car_year VARCHAR(10) NOT NULL,
        car_power VARCHAR(20) NOT NULL,
        seats_number TINYINT UNSIGNED NOT NULL ,
        registration_number VARCHAR (20) UNIQUE NOT NULL ,
        registration_date DATE NOT NULL ,
        created_at TIMESTAMP NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);



# Table: rides
CREATE TABLE rides(
        ride_id INT AUTO_INCREMENT PRIMARY KEY ,
        driver_id INT NOT NULL,
        departure_date_time DATETIME NOT NULL ,
        departure_place VARCHAR (100) NOT NULL ,
        arrival_date_time DATETIME NOT NULL ,
        arrival_place VARCHAR (100) NOT NULL ,
        price DECIMAL(6,2) NOT NULL ,
        available_seats TINYINT UNSIGNED NOT NULL ,
        ride_status VARCHAR(20) NOT NULL,
        commission INT NOT NULL,
        created_at TIMESTAMP NOT NULL,
        updated_at TIMESTAMP NOT NULL,
        FOREIGN KEY (driver_id) REFERENCES users(user_id) ON DELETE CASCADE
);



# Table: ride_passengers
CREATE TABLE ride_passengers (
        ride_id INT NOT NULL,
        user_id INT NOT NULL,
        PRIMARY KEY (ride_id, user_id),
        FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


# Table: bookings
CREATE TABLE bookings(
        booking_id INT AUTO_INCREMENT PRIMARY KEY ,
        ride_id INT NOT NULL,
        passenger_id INT NOT NULL,
        driver_id INT NOT NULL,
        booking_status VARCHAR(20) NOT NULL,
        created_at TIMESTAMP NOT NULL,
        updated_at TIMESTAMP NOT NULL,
        FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
        FOREIGN KEY (passenger_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES users(user_id) ON DELETE CASCADE
);


# Remplissage de la table rôles avec la définission des rôles
INSERT INTO roles(role_name) VALUES ('passager'), ('conducteur'), ('employé'), ('admin');



# Ajout d'utilisateurs conducteur
INSERT INTO users(
        user_id, last_name, first_name, email, password, login, phone, address, city, zip_code, picture, licence_no, credits, preferences
) VALUES
(
        2,
        'DUPONT',
        'Alexandre',
        'a.dupont@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'adupont', 
        '0612345678',
        '25 rue de la Liberté',
        'Paris',
        '75011',
        '/uploads/profiles/alexandre_dupont.jpg',
        'A123456789',
        150,
        JSON_OBJECT(
        'smoker', true,
        'pet', false,
        'note', ''
    ));

INSERT INTO users(
        user_id, last_name, first_name, email, password, login, phone, address, city, zip_code, picture, licence_no, credits, preferences
) VALUES
(
        3,
        'GARCIA',
        'Pedro',
        'p.garcia@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'gaga588', 
        '0612345678',
        '25 rue de la Liberté',
        'Paris',
        '75011',
        NULL,
        'A125557809',
       25,
        JSON_OBJECT(
        'smoker', false,
        'pet', true,
        'note', ''
    ));

INSERT INTO users(
        user_id, last_name, first_name, email, password, login, phone, address, city, zip_code, picture, licence_no, credits, preferences
) VALUES
(
        4,
        'JOHNSON',
        'Kevin',
        'k.jojo@email.com',
        '$2y$10$7sSTXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/xyz', 
        'keke25', 
        '0612355478',
        '25 rue de la Liberté',
        'Paris',
        '75011',
        NULL,
        'A125566809',
       45,
        JSON_OBJECT(
        'smoker',true,
        'pet', true,
        'note', ''
    ));


INSERT INTO user_roles(user_id, role_id) VALUE(2,2),(3,2),(4,2);


#Ajout des trajets 
INSERT into rides(
        driver_id, departure_date_time,departure_place, arrival_date_time, arrival_place, price, available_seats, ride_status, commission
) VALUES 
        (2, '2025-09-25 09:00:00','Paris','2025-09-25 12:00:00','Lyon',25,3,'Disponible',2), 
        (3, '2025-09-25 10:00:00','Paris','2025-09-25 13:00:00','Lyon',20,1,'Disponible',2), 
        (4, '2025-09-25 10:00:00','Paris','2025-09-25 13:00:00','Lyon',20,0,'Complet',2);