DROP DATABASE ecoride_db;


CREATE DATABASE IF NOT EXISTS ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;



USE ecoride_db;

#------------------------------------------------------------
#        Script MySQL.
#------------------------------------------------------------


#------------------------------------------------------------
# Table: users
#------------------------------------------------------------

CREATE TABLE users(
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        last_name VARCHAR (50) NOT NULL,
        first_name VARCHAR (50) NOT NULL,
        user_name VARCHAR (50) NULL UNIQUE,
        email VARCHAR (150) NOT NULL UNIQUE,
        password VARCHAR (255) NOT NULL,
        role ENUM('passager', 'conduteur', 'lesdeux', 'employer', 'admin') NOT NULL DEFAULT 'passager',
        phone VARCHAR (20) NULL,
        address VARCHAR (100) NULL,
        city VARCHAR (50) NULL,
        zip_code VARCHAR (10) NULL,
        picture VARCHAR (200) NULL,
        licence_no VARCHAR (50) NULL,
        credit INT UNSIGNED NULL DEFAULT 0,
        api_token CHAR(64) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


#------------------------------------------------------------
# Table: cars
#------------------------------------------------------------

CREATE TABLE cars(
        car_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_brand VARCHAR (100) NOT NULL ,
        car_model VARCHAR (100) NOT NULL ,
        car_color VARCHAR (50) NOT NULL ,
        car_year YEAR NOT NULL,
        car_power ENUM ('diesel', 'essence', 'electrique', 'hybride', 'gpl') NOT NULL,
        seats_number TINYINT UNSIGNED NOT NULL ,
        registration_number VARCHAR (20) UNIQUE NOT NULL ,
        registration_date DATE NOT NULL ,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


#------------------------------------------------------------
# Table: rides
#------------------------------------------------------------

CREATE TABLE rides(
        ride_id INT AUTO_INCREMENT PRIMARY KEY ,
        driver_id INT NOT NULL,
        departure_date_time DATE NOT NULL ,
        departure_place VARCHAR (100) NOT NULL ,
        arrival_date_time DATE NOT NULL ,
        arrival_place VARCHAR (100) NOT NULL ,
        duration_minutes INT UNSIGNED NOT NULL ,
        price DECIMAL(6,2) NOT NULL ,
        available_seats TINYINT UNSIGNED NOT NULL ,
        status ENUM('disponible', 'complet', 'annulé', 'passé'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES users(user_id) ON DELETE CASCADE
);


#------------------------------------------------------------
# Table: bookings
#------------------------------------------------------------

CREATE TABLE bookings(
        booking_id INT AUTO_INCREMENT PRIMARY KEY ,
        ride_id INT NOT NULL,
        passenger_id INT NOT NULL,
        booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM ('confirmed', 'canceled', 'invoiced') NOT NULL,
        FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
        FOREIGN KEY (passenger_id) REFERENCES users(user_id) ON DELETE CASCADE
);


#------------------------------------------------------------
# Table: reviews
#------------------------------------------------------------

CREATE TABLE reviews(
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        ride_id INT NOT NULL,
        author_id INT NOT NULL,
        target_id INT NOT NULL,
        content VARCHAR(1000),
        note TINYINT UNSIGNED NOT NULL CHECK (note BETWEEN 0 AND 10),
        statut ENUM('pending', 'approved', 'rejected')NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        validated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
FOREIGN KEY (target_id) REFERENCES users(user_id) ON DELETE CASCADE
);
