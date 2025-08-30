CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin account
INSERT INTO `staff` (`name`, `email`, `password_hash`, `role`)
VALUES ('Administrator', 'admin@tcc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE `role` = 'admin';

-- Create health_records table if it doesn't exist
CREATE TABLE IF NOT EXISTS `health_records` (
  `health_id` INT PRIMARY KEY AUTO_INCREMENT,
  `tortoise_id` INT NOT NULL,
  `staff_id` INT NOT NULL,
  `check_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `weight` DECIMAL(10,2) NOT NULL,
  `temperature` DECIMAL(4,1) NOT NULL,
  `health_status` ENUM('Healthy', 'Needs Attention', 'Critical') NOT NULL,
  `notes` TEXT,
  FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises`(`tortoise_id`),
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`staff_id`)
);

-- Create feeding_records table if it doesn't exist
CREATE TABLE IF NOT EXISTS `feeding_records` (
  `feeding_id` INT PRIMARY KEY AUTO_INCREMENT,
  `tortoise_id` INT NOT NULL,
  `staff_id` INT NOT NULL,
  `feed_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `food_type` VARCHAR(50) NOT NULL,
  `quantity` INT NOT NULL,
  FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises`(`tortoise_id`),
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`staff_id`)
);

-- Create enclosures table if it doesn't exist
CREATE TABLE IF NOT EXISTS `enclosures` (
  `enclosure_id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `capacity` INT NOT NULL,
  `current_occupancy` INT DEFAULT 0,
  `temperature` DECIMAL(4,1),
  `humidity` DECIMAL(4,1),
  `last_cleaned` TIMESTAMP,
  `notes` TEXT
);

-- Create breeding_events table if it doesn't exist
CREATE TABLE IF NOT EXISTS `breeding_events` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `breeding_id` VARCHAR(50) NOT NULL,
  `breeding_date` DATE NOT NULL,
  `offspring_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create breeding_seasons table if it doesn't exist
CREATE TABLE IF NOT EXISTS `breeding_seasons` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `start_month` VARCHAR(20) NOT NULL,
  `end_month` VARCHAR(20) NOT NULL,
  `temperature_range` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
