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

CREATE TABLE meetings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  location_name VARCHAR(190) NULL,
  location_details VARCHAR(255) NULL,
  featured_snack VARCHAR(190) NULL,
  focus_topic VARCHAR(190) NULL,
  presenter_name VARCHAR(190) NULL,
  presenter_topic VARCHAR(190) NULL,
  special_note TEXT NULL,
  status ENUM('draft','scheduled','cancelled') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@taosarts.org', '$2y$12$/./MbNypsteGATAydMcZNOdWbs3C2u2aw6jdByojQP56SQF94mpu6', 'admin');

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

INSERT INTO meetings (title, slug, starts_at, ends_at, location_name, location_details, featured_snack, focus_topic, presenter_name, presenter_topic, special_note, status) VALUES
('Critique Club Kickoff', 'critique-club-kickoff', '2026-05-03 12:00:00', '2026-05-03 15:00:00', 'Taos Arts Studio', 'Main room, bring one work in progress if you have it.', 'Biscochitos and tea', 'Introductions and gentle first-round critique', 'Marisol', 'How to ask for useful feedback', 'Our first gathering will stay extra welcoming and low-pressure for new folks.', 'scheduled'),
('Materials and Process Circle', 'materials-and-process-circle', '2026-05-10 12:00:00', '2026-05-10 15:00:00', 'Taos Arts Studio', 'Back patio if the weather is good.', 'Fresh fruit and cookies', 'Material choices, process notes, and works in transition', 'Devon', 'Five-minute demo on photographing artwork for critique', 'This week especially welcomes painters, fiber artists, and mixed media makers.', 'scheduled'),
('Story and Image Session', 'story-and-image-session', '2026-05-17 12:00:00', '2026-05-17 15:00:00', 'Harwood side classroom', 'Enter through the north door.', 'Savory hand pies', 'Narrative, sequence, and visual storytelling', 'Asha', 'Short talk on sequencing images for stronger storytelling', 'We will leave extra time for members working in series.', 'scheduled');

INSERT INTO stories (title, slug, artist_name, excerpt, body, featured_image, status, published_at) VALUES
('Painting Place Into Memory', 'painting-place-into-memory', 'Elena Martinez', 'A Taos painter reflects on landscape, memory, and how critique can help a body of work become more coherent over time.', 'Elena Martinez works from sketches, remembered color, and long walks around Taos mesa roads. In critique club, she is interested in the moment when a painting stops being only a landscape and starts holding emotional weather too.\n\nShe hopes the group becomes a place where artists can test unfinished work, talk honestly about process, and practice offering observations that are both specific and generous. She is especially interested in how other artists read rhythm, distance, and atmosphere across a series.\n\nFor Elena, good critique does not flatten difference. It helps each artist hear what is already alive in the work and what wants to become clearer.', 'assets/img/site/artist-story.png', 'published', '2026-04-26 16:30:00');

INSERT INTO galleries (title, slug, description, status) VALUES
('Rooms of Light and Land', 'rooms-of-light-and-land', 'A sample gallery of Taos-inspired interiors, paintings, and handmade objects arranged in warm conversation with one another.', 'published');

INSERT INTO gallery_images (gallery_id, file_path, caption, alt_text, sort_order) VALUES
((SELECT id FROM galleries WHERE slug='rooms-of-light-and-land' LIMIT 1), 'assets/img/site/gallery-room.png', 'Paintings, ceramics, and woven work sharing one warm Taos room.', 'Taos gallery interior with landscape paintings, ceramics, and woven art.', 1),
((SELECT id FROM galleries WHERE slug='rooms-of-light-and-land' LIMIT 1), 'assets/img/site/hero-doorway.png', 'A threshold image for the club: land, sky, and weathered turquoise wood.', 'Adobe doorway opening onto the Taos landscape.', 2),
((SELECT id FROM galleries WHERE slug='rooms-of-light-and-land' LIMIT 1), 'assets/img/site/critique-circle.png', 'Community as part of the visual landscape: artists gathered in conversation.', 'Artists gathered in a warm studio critique circle.', 3);
