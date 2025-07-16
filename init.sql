CREATE DATABASE IF NOT EXISTS if0_39360065_donation_db;
USE if0_39360065_donation_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    gender VARCHAR(10),
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'assets/images/default-profile.png',
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NOT NULL,
    status ENUM('available', 'pending', 'admin_review', 'matched') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'admin_review', 'matched') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donation_id INT NOT NULL,
    donation_user_id INT NOT NULL,
    request_id INT NOT NULL,
    request_user_id INT NOT NULL,
    status ENUM('pending', 'admin_review', 'completed', 'rejected') DEFAULT 'pending',
    message TEXT,
    admin_notes TEXT,
    admin_approved BOOLEAN DEFAULT 0,
    admin_reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    matched_at TIMESTAMP NULL,

    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (donation_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (request_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);
    -- UNIQUE KEY (donation_id, request_id)

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert initial classification
INSERT INTO categories (name, description) VALUES
('Clothing', 'All types of clothing items'),
('Furniture', 'Household furniture items'),
('Electronics', 'Electronic devices and accessories'),
('Books', 'Educational and recreational books'),
('Toys', 'Children toys and games'),
('Kitchenware', 'Kitchen utensils and appliances'),
('Sports', 'Sports equipment and gear'),
('Other', 'Miscellaneous items');

-- Create an administrator account (password: 123)
INSERT INTO users (email, password_hash, full_name, role) 
VALUES ('admin@test.com', '$2y$10$UqfRu2PSIv.FDfeoufjmmerPDZbIE5LRNtKb.PQYlMB.4xAyWKDk.', 'Administrator', 'admin');