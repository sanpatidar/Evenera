USE evenera;

CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (name, email, role) VALUES 
    ('Admin User', 'admin@evenera.com', 'admin'),
    ('Support Team', 'support@evenera.com', 'support'),
    ('Event Manager', 'events@evenera.com', 'manager'); 