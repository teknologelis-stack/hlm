-- Mikrotik Devices Table
-- Version: 1.0.5

CREATE TABLE IF NOT EXISTS mikrotik_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    port INT DEFAULT 8728,
    description TEXT,
    status ENUM('online', 'offline', 'unknown') DEFAULT 'unknown',
    cpu_usage INT DEFAULT 0,
    ram_usage INT DEFAULT 0,
    uptime VARCHAR(100),
    ros_version VARCHAR(50),
    pppoe_count INT DEFAULT 0,
    interface_count INT DEFAULT 0,
    last_connect DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
