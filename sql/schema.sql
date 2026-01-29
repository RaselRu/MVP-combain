CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT DEFAULT NULL,
  status VARCHAR(60) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE project_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  member_role VARCHAR(60) NOT NULL DEFAULT 'member',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_project_user (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tnved_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  duty_rate DECIMAL(7,4) NOT NULL DEFAULT 0,
  vat_rate DECIMAL(7,4) NOT NULL DEFAULT 0,
  comments TEXT DEFAULT NULL,
  tags VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ddp_calculations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  tnved_code_id INT DEFAULT NULL,
  cost DECIMAL(14,2) NOT NULL DEFAULT 0,
  freight DECIMAL(14,2) NOT NULL DEFAULT 0,
  insurance DECIMAL(14,2) NOT NULL DEFAULT 0,
  other_costs DECIMAL(14,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'RUB',
  fx_rate DECIMAL(14,6) NOT NULL DEFAULT 1,
  duty_rate DECIMAL(7,4) NOT NULL DEFAULT 0,
  vat_rate DECIMAL(7,4) NOT NULL DEFAULT 0,
  customs_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  duty_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  vat_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  breakdown_text TEXT DEFAULT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'confirmed',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (tnved_code_id) REFERENCES tnved_codes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE history_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  action VARCHAR(120) NOT NULL,
  details_json TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bookmarks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  scope VARCHAR(20) NOT NULL DEFAULT 'personal',
  item_type VARCHAR(40) NOT NULL,
  item_ref VARCHAR(120) DEFAULT NULL,
  title VARCHAR(160) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reference_values (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref_type VARCHAR(60) NOT NULL,
  code VARCHAR(40) NOT NULL,
  label VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (name, description) VALUES
  ('admin', 'Administrator'),
  ('user', 'Standard user');

INSERT INTO reference_values (ref_type, code, label, is_active, sort_order, created_at) VALUES
  ('currency', 'RUB', 'RUB', 1, 1, NOW()),
  ('currency', 'USD', 'USD', 1, 2, NOW()),
  ('currency', 'EUR', 'EUR', 1, 3, NOW()),
  ('incoterm', 'DDP', 'DDP', 1, 1, NOW());
