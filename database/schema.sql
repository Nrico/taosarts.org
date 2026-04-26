CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_signups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  source VARCHAR(100) DEFAULT 'website',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prompt TEXT NOT NULL,
  help_text TEXT NULL,
  question_type ENUM('text','textarea','email','select','checkbox','radio','number') NOT NULL DEFAULT 'textarea',
  options_json JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE questionnaires (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  intro TEXT NULL,
  status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE questionnaire_questions (
  questionnaire_id INT NOT NULL,
  question_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (questionnaire_id, question_id),
  FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE questionnaire_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  questionnaire_id INT NOT NULL,
  responder_email VARCHAR(190) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
);

CREATE TABLE response_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  response_id INT NOT NULL,
  question_id INT NOT NULL,
  answer_text TEXT NULL,
  FOREIGN KEY (response_id) REFERENCES questionnaire_responses(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE stories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  artist_name VARCHAR(190) NULL,
  excerpt TEXT NULL,
  body MEDIUMTEXT NULL,
  featured_image VARCHAR(255) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE galleries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  description TEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE gallery_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gallery_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  caption VARCHAR(255) NULL,
  alt_text VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@taosarts.org', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'admin');

INSERT INTO questions (prompt, help_text, question_type, options_json) VALUES
('What kind of artist are you?', 'Painter, photographer, sculptor, writer, designer, maker or something else.', 'textarea', NULL),
('What would you most like to talk about in critique club?', NULL, 'textarea', NULL),
('What are two things you hope this group helps artists with?', NULL, 'textarea', NULL),
('How would you like to contribute?', NULL, 'checkbox', JSON_ARRAY('Bring snacks','Help facilitate','Share resources','Host a session','Invite artists')),
('What feels intimidating about critique?', NULL, 'textarea', NULL);

INSERT INTO questionnaires (title, slug, intro, status) VALUES
('Taos Arts Community Survey', 'community-survey', 'Help shape a warm, useful critique club rooted in Taos.', 'active');

INSERT INTO questionnaire_questions (questionnaire_id, question_id, sort_order, is_required) VALUES
(1,1,1,1),(1,2,2,1),(1,3,3,0),(1,4,4,0),(1,5,5,0);
