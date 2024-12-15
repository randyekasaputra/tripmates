CREATE DATABASE traveling;
USE traveling;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    bio TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    destination VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    max_participants INT NOT NULL,
    price DECIMAL(10,2),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE trip_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT,
    user_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tambahkan tabel payments
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_participant_id INT,
    amount DECIMAL(10,2),
    payment_method ENUM('bank_transfer', 'e-wallet') NOT NULL,
    payment_proof VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_participant_id) REFERENCES trip_participants(id)
);

-- Tambahkan user default untuk testing
INSERT INTO users (username, email, password, full_name) 
VALUES ('admin', 'admin@tripmates.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin TripMates');

-- Tambahkan beberapa trip default
INSERT INTO trips (user_id, destination, description, start_date, end_date, max_participants, price) 
VALUES 
(1, 'Bali', 'Explore the beauty of Bali', '2024-03-20', '2024-03-25', 5, 2500000),
(1, 'Lombok', 'Adventure in Lombok', '2024-04-01', '2024-04-05', 4, 2000000),
(1, 'Raja Ampat', 'Diving in Raja Ampat', '2024-05-10', '2024-05-15', 3, 5000000); 

CREATE TABLE collection_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    trip_id INT NOT NULL,
    payment_method ENUM('bank_transfer', 'dana') NOT NULL,
    bank_name VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    payment_proof VARCHAR(255) NOT NULL,  -- Kolom untuk menyimpan path file gambar
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id)
);

-- Tabel untuk menyimpan history pembayaran
CREATE TABLE payment_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    collection_payment_id INT NOT NULL,
    status ENUM('pending', 'verified', 'rejected') NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collection_payment_id) REFERENCES collection_payments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
); 