-- =========================================================
-- PetNest Database Schema
-- Purpose: Complete 8-table MySQL database script.
-- Scope: Database / System
-- =========================================================
CREATE DATABASE IF NOT EXISTS `petnest_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `petnest_db`;

-- Disable foreign key checks during table recreation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `emergency_alerts`;
DROP TABLE IF EXISTS `ratings`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `instructions`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `pets`;
DROP TABLE IF EXISTS `keeper_profiles`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. Table structure for table `users`
-- ----------------------------------------------------------
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('admin', 'operator', 'owner', 'keeper') NOT NULL DEFAULT 'owner',
  `profile_photo` VARCHAR(255) DEFAULT 'default_avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table structure for table `keeper_profiles`
-- ----------------------------------------------------------
CREATE TABLE `keeper_profiles` (
  `profile_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `bio` TEXT DEFAULT NULL,
  `location` VARCHAR(100) NOT NULL,
  `price_per_day` DECIMAL(10,2) NOT NULL DEFAULT 20.00,
  `breeds_experienced` TEXT DEFAULT NULL,
  `availability_status` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
  `years_experience` INT DEFAULT 1,
  `is_verified` TINYINT(1) DEFAULT 0,
  CONSTRAINT `fk_keeper_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table structure for table `pets`
-- ----------------------------------------------------------
CREATE TABLE `pets` (
  `pet_id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `pet_name` VARCHAR(100) NOT NULL,
  `breed` VARCHAR(100) NOT NULL,
  `species` VARCHAR(50) NOT NULL DEFAULT 'Dog',
  `age` INT NOT NULL DEFAULT 1,
  `weight` DECIMAL(5,2) DEFAULT NULL,
  `pet_photo` VARCHAR(255) DEFAULT 'default_pet.png',
  `medical_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pet_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Table structure for table `bookings`
-- ----------------------------------------------------------
CREATE TABLE `bookings` (
  `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `pet_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `keeper_id` INT NOT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `num_days` INT NOT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `surcharge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'active', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `keeper_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_booking_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. Table structure for table `instructions`
-- ----------------------------------------------------------
CREATE TABLE `instructions` (
  `instruction_id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `feeding_schedule` TEXT DEFAULT NULL,
  `bath_schedule` TEXT DEFAULT NULL,
  `medicine_details` TEXT DEFAULT NULL,
  `special_notes` TEXT DEFAULT NULL,
  `instruction_surcharge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_instruction_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. Table structure for table `payments`
-- ----------------------------------------------------------
CREATE TABLE `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'stripe',
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. Table structure for table `ratings`
-- ----------------------------------------------------------
CREATE TABLE `ratings` (
  `rating_id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `keeper_id` INT NOT NULL,
  `stars` TINYINT(1) NOT NULL CHECK (`stars` BETWEEN 1 AND 5),
  `feedback_text` TEXT DEFAULT NULL,
  `rated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rating_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. Table structure for table `emergency_alerts`
-- ----------------------------------------------------------
CREATE TABLE `emergency_alerts` (
  `alert_id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `keeper_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_alert_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alert_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alert_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- SEED DATA FOR TESTING (Default password for all: password123)
-- Password hash: $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm
-- ==========================================================

-- 1. Insert Users
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `phone`, `role`, `profile_photo`, `is_active`) VALUES
(1, 'System Administrator', 'admin@petnest.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '0771234567', 'admin', 'default_avatar.png', 1),
(2, 'Platform Operator', 'operator@petnest.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '0777654321', 'operator', 'default_avatar.png', 1),
(3, 'Sarah Jenkins', 'sarah@petnest.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '0712345678', 'keeper', 'default_avatar.png', 1),
(4, 'David Silva', 'david@petnest.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '0765432190', 'keeper', 'default_avatar.png', 1),
(5, 'Janani Perera', 'janani@petnest.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '0789012345', 'owner', 'default_avatar.png', 1);

-- 2. Insert Keeper Profiles
INSERT INTO `keeper_profiles` (`profile_id`, `user_id`, `bio`, `location`, `price_per_day`, `breeds_experienced`, `availability_status`, `years_experience`, `is_verified`) VALUES
(1, 3, 'Passionate animal lover with 5+ years of experience boarding dogs and cats in a spacious home with a fenced backyard.', 'Colombo', 25.00, 'Golden Retriever, Labrador, Poodle, Persian Cat', 'available', 5, 1),
(2, 4, 'Certified veterinary assistant offering dedicated pet care and special medical boarding for senior dogs.', 'Kandy', 30.00, 'German Shepherd, Husky, Pug, Beagle, British Shorthair', 'available', 4, 1);

-- 3. Insert Pets
INSERT INTO `pets` (`pet_id`, `owner_id`, `pet_name`, `breed`, `species`, `age`, `weight`, `pet_photo`, `medical_notes`) VALUES
(1, 5, 'Max', 'Golden Retriever', 'Dog', 3, 28.50, 'default_pet.png', 'Healthy, regular flea treatment, allergic to chicken.'),
(2, 5, 'Luna', 'Persian Cat', 'Cat', 2, 4.20, 'default_pet.png', 'Requires daily brushing, gentle diet.');

-- 4. Insert Sample Booking
INSERT INTO `bookings` (`booking_id`, `pet_id`, `owner_id`, `keeper_id`, `check_in_date`, `check_out_date`, `num_days`, `base_price`, `surcharge`, `total_price`, `status`, `keeper_notes`) VALUES
(1, 1, 5, 3, '2026-09-10', '2026-09-15', 5, 125.00, 10.00, 135.00, 'confirmed', 'Looking forward to hosting Max!');

-- 5. Insert Sample Care Instructions
INSERT INTO `instructions` (`instruction_id`, `booking_id`, `feeding_schedule`, `bath_schedule`, `medicine_details`, `special_notes`, `instruction_surcharge`) VALUES
(1, 1, '2 cups of salmon kibble at 8:00 AM and 6:00 PM.', 'Bath once on the 3rd day.', 'Give omega-3 oil capsule once daily with morning food.', 'Loves playing fetch in the garden.', 10.00);

-- 6. Insert Sample Payment
INSERT INTO `payments` (`payment_id`, `booking_id`, `owner_id`, `amount`, `payment_method`, `transaction_id`, `payment_status`) VALUES
(1, 1, 5, 135.00, 'stripe', 'txn_test_987654321', 'paid');

-- 7. Insert Sample Rating
INSERT INTO `ratings` (`rating_id`, `booking_id`, `owner_id`, `keeper_id`, `stars`, `feedback_text`) VALUES
(1, 1, 5, 3, 5, 'Sarah was fantastic! Max was treated like family and came home very happy. Highly recommended!');
