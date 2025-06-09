# projet-fin-d-etude-SGR
Projet de fin d'étude : Développement d’une application web de gestion des réclamations pour une entreprise. L’application permet :  La soumission et le suivi des réclamations clients  La gestion des statuts des réclamations  La communication entre les clients et le service de support  La génération de rapports pour l’analyse des réclamations
pour que le projet fonction vous devez entrez cet script sql dans votre database pour ajouter les tables essentielles

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS complaint_management_system;
USE complaint_management_system;

-- Table des utilisateurs
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Table des plaintes
CREATE TABLE complaints (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table des notifications
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table des paramètres système
CREATE TABLE settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table du journal d'activité
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Insertion des données initiales

-- Création d'un administrateur par défaut
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Administrateur Système', 'admin');

-- Insertion des paramètres système par défaut
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Système de Gestion des Plaintes'),
('max_file_size', '5242880'),
('allowed_file_types', 'jpg,jpeg,png,pdf');

-- Création des index pour optimiser les performances
CREATE INDEX idx_complaints_status ON complaints(status);
CREATE INDEX idx_complaints_user ON complaints(user_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_activity_log_user ON activity_log(user_id); 

Pour utiliser ce script :
Créez une nouvelle base de données dans MySQL
Copiez et exécutez ce script
Le mot de passe par défaut pour l'admin est hashé (vous devrez le changer)
merci
