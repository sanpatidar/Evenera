DROP TABLE IF EXISTS shopping_items;
DROP TABLE IF EXISTS shopping_tasks;
DROP TABLE IF EXISTS shopping_events;

CREATE TABLE shopping_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    location VARCHAR(255),
    description TEXT,
    status VARCHAR(20) DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE shopping_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    priority VARCHAR(20),
    quantity INT,
    budget DECIMAL(10,2),
    vendor VARCHAR(255),
    delivery_date DATE,
    notes TEXT,
    purchased BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES shopping_events(id) ON DELETE CASCADE
);

CREATE TABLE shopping_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    priority VARCHAR(20),
    due_date DATE,
    assigned_to VARCHAR(255),
    notes TEXT,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES shopping_events(id) ON DELETE CASCADE
); 