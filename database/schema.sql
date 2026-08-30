-- taosarts.org — editorial schema
-- Plain MySQL/MariaDB, no ORM. Run this against a fresh database; it replaces
-- the old gallery/questionnaire/meeting/story tables entirely (dropped per
-- product decision to run as a pure editorial magazine).
--
-- "Spark" is a story-selection concept, not a content format: story_ideas
-- holds the research trigger (spark_url/snippet, a candidate connection,
-- suggested images/links) so a human can decide what's worth writing.
-- stories.body_markdown is just the finished piece — normal prose, no
-- mandated structure — once an idea has been written up.

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

-- images before entities: entities.portrait_image_id references it, so it has
-- to exist first (the original table order in the brief had this backwards —
-- MySQL/MariaDB will refuse to create a FK against a table that doesn't exist
-- yet — this file just reorders the CREATEs, no column changed).
CREATE TABLE images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(1000) NOT NULL,
  thumbnail_url VARCHAR(1000),
  source VARCHAR(255) NOT NULL,
  license VARCHAR(255) NOT NULL,
  credit_text VARCHAR(500) NOT NULL,
  original_url VARCHAR(1000),
  -- Focal point as a percentage (0-100) of the image, used as CSS
  -- object-position wherever a card/hero crops the image with
  -- object-fit:cover. Default 50/50 (center) needs no migration for
  -- existing rows. Addition beyond the original brief's schema, added to
  -- support cropping images correctly per subject.
  focal_x TINYINT UNSIGNED NOT NULL DEFAULT 50,
  focal_y TINYINT UNSIGNED NOT NULL DEFAULT 50,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('person','place','tradition') NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  one_line_id VARCHAR(255),
  aliases JSON,
  keywords JSON,
  date_start VARCHAR(50),
  date_end VARCHAR(50),
  coordinates VARCHAR(100),
  cadence VARCHAR(100),
  portrait_image_id INT UNSIGNED,
  bio_markdown TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (portrait_image_id) REFERENCES images(id) ON DELETE SET NULL,
  INDEX (type),
  INDEX (slug)
);

CREATE TABLE story_ideas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id VARCHAR(100),
  spark_url VARCHAR(1000),
  spark_source VARCHAR(255),
  spark_snippet TEXT,
  detected_via VARCHAR(100),
  matched_entity_id INT UNSIGNED,
  proposed_new_entity JSON,
  connection_note TEXT,
  -- Optional full-length draft a research agent may submit alongside the
  -- idea. Additive — every existing caller that never sends this field is
  -- unaffected; NULL is the normal case for anything ingested before this
  -- column existed or from a source that only proposes ideas, not drafts.
  draft_markdown LONGTEXT,
  suggested_images JSON,
  suggested_links JSON,
  status ENUM('new','culled','writing','published','rejected') DEFAULT 'new',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (matched_entity_id) REFERENCES entities(id) ON DELETE SET NULL,
  INDEX (status),
  INDEX (batch_id)
);

CREATE TABLE stories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  story_idea_id INT UNSIGNED,
  title VARCHAR(255) NOT NULL,
  dek VARCHAR(500),
  slug VARCHAR(255) NOT NULL UNIQUE,
  body_markdown LONGTEXT NOT NULL,
  spark_url VARCHAR(1000),
  spark_source VARCHAR(255),
  -- Which linked entity (via story_entities) this story is actually about,
  -- for the card grid's color-coded type tag. Not derivable from
  -- story_entities alone (it's an unordered set) — an editor picks it
  -- explicitly. Addition beyond the original brief's schema.
  primary_entity_id INT UNSIGNED,
  status ENUM('draft','published') DEFAULT 'draft',
  published_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (story_idea_id) REFERENCES story_ideas(id) ON DELETE SET NULL,
  FOREIGN KEY (primary_entity_id) REFERENCES entities(id) ON DELETE SET NULL,
  INDEX (status),
  INDEX (published_at)
);

CREATE TABLE story_entities (
  story_id INT UNSIGNED NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (story_id, entity_id),
  FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

CREATE TABLE story_images (
  story_id INT UNSIGNED NOT NULL,
  image_id INT UNSIGNED NOT NULL,
  sort_order INT DEFAULT 0,
  PRIMARY KEY (story_id, image_id),
  FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
);

CREATE TABLE api_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_hash VARCHAR(255) NOT NULL,
  label VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME
);

-- Addition beyond the given schema: the ingest endpoint is required to "log
-- and rate-limit repeated auth failures" — there's nowhere in the tables
-- above to put that, so this table exists solely to support that requirement.
-- Nothing else reads or writes it.
CREATE TABLE api_auth_failures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(64) NOT NULL,
  attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (ip_address, attempted_at)
);

-- Seed --------------------------------------------------------------------
-- Real images and facts throughout (Wikimedia Commons / Library of Congress
-- / National Archives, all verified reusable and hotlinked from
-- upload.wikimedia.org), verified against Wikipedia at write time. The one
-- deliberately fictional part of each story is its opening present-day hook
-- (the thing a research agent would have flagged as the spark) — kept
-- generic (a cooperative, a printmaker, a museum's own program) rather than
-- naming a real private person doing something they didn't do.

INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@taosarts.org', '$2y$12$/./MbNypsteGATAydMcZNOdWbs3C2u2aw6jdByojQP56SQF94mpu6', 'admin');

INSERT INTO images (id, url, thumbnail_url, source, license, credit_text, original_url) VALUES
(1, 'https://upload.wikimedia.org/wikipedia/commons/4/48/Miss_Mary_Millicent_Rogers_Vogue_1920-05-01.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2c/Millicent_Rogers_profile_portrait.jpg/330px-Millicent_Rogers_profile_portrait.jpg', 'Library of Congress — Chronicling America', 'Public Domain', 'Millicent Rogers, profile portrait by Charlotte Fairchild. Richmond Times-Dispatch, 1920. Library of Congress, Public Domain.', 'https://commons.wikimedia.org/wiki/File:Millicent_Rogers_profile_portrait.jpg'),
(2, 'https://upload.wikimedia.org/wikipedia/commons/6/69/Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg/330px-Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg', 'Wikimedia Commons', 'Public Domain', 'Bert Phillips and Ernest Blumenschein beside the broken wagon wheel, Taos, 1898. Public domain, Wikimedia Commons.', 'https://commons.wikimedia.org/wiki/File:Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg'),
(3, 'https://upload.wikimedia.org/wikipedia/commons/6/61/Taos_County%2C_New_Mexico._Patrocina_Barela%2C_woodcarver_-_NARA_-_521865.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/61/Taos_County%2C_New_Mexico._Patrocina_Barela%2C_woodcarver_-_NARA_-_521865.jpg/330px-Taos_County%2C_New_Mexico._Patrocina_Barela%2C_woodcarver_-_NARA_-_521865.jpg', 'U.S. National Archives and Records Administration', 'Public Domain', 'Patrocino Barela, woodcarver, Taos County, N.M. Photo: Irving Rusinow, 1941. U.S. National Archives, Public Domain.', 'https://commons.wikimedia.org/wiki/File:Taos_County,_New_Mexico._Patrocina_Barela,_woodcarver_-_NARA_-_521865.jpg'),
(4, 'https://upload.wikimedia.org/wikipedia/commons/1/17/Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg/330px-Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg', 'Library of Congress', 'Public Domain', 'Mabel Dodge Luhan, portrait by Carl Van Vechten. Library of Congress, Public Domain.', 'https://commons.wikimedia.org/wiki/File:Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg'),
(5, 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Lawrence_Ranch-view_towards_Taos.JPG', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Lawrence_Ranch-view_towards_Taos.JPG/330px-Lawrence_Ranch-view_towards_Taos.JPG', 'Wikimedia Commons', 'CC0', 'D.H. Lawrence Ranch, view toward Taos. Photo: Vivaverdi, CC0.', 'https://commons.wikimedia.org/wiki/File:Lawrence_Ranch-view_towards_Taos.JPG'),
(6, 'https://upload.wikimedia.org/wikipedia/commons/4/4f/Taos_Pueblo_2017-05-05.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Taos_Pueblo_2017-05-05.jpg/330px-Taos_Pueblo_2017-05-05.jpg', 'Wikimedia Commons', 'CC BY-SA 4.0', 'Taos Pueblo, multi-level adobe dwelling. Photo: John Mackenzie Burke, CC BY-SA 4.0.', 'https://commons.wikimedia.org/wiki/File:Taos_Pueblo_2017-05-05.jpg'),
(7, 'https://upload.wikimedia.org/wikipedia/commons/8/80/Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg/330px-Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg', 'Wikimedia Commons', 'CC BY-SA 2.0', 'Navajo third-phase wearing blanket, c. 1890–95, Millicent Rogers Museum, Taos. Photo: Peter D. Tillman, CC BY-SA 2.0.', 'https://commons.wikimedia.org/wiki/File:Navajo_Third_phase_wearing_blanket,_circa_1890-95._Millicent_Rogers_Museum,_Taos.jpg'),
(8, 'https://upload.wikimedia.org/wikipedia/commons/9/9b/Taos_plaza_la_fonda.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9b/Taos_plaza_la_fonda.jpg/330px-Taos_plaza_la_fonda.jpg', 'Wikimedia Commons', 'CC BY 2.5', 'Taos Plaza, near La Fonda de Taos. Photo: Zeality, CC BY 2.5.', 'https://commons.wikimedia.org/wiki/File:Taos_plaza_la_fonda.jpg'),
(9, 'https://upload.wikimedia.org/wikipedia/commons/0/06/Archives_of_American_Art_-_Patroci%C3%B1o_Barela_-_Portrait_3264.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/06/Archives_of_American_Art_-_Patroci%C3%B1o_Barela_-_Portrait_3264.jpg/330px-Archives_of_American_Art_-_Patroci%C3%B1o_Barela_-_Portrait_3264.jpg', 'Archives of American Art, Smithsonian Institution', 'Public Domain', 'Patrociño Barela, portrait. Archives of American Art, Smithsonian Institution, Public Domain.', 'https://commons.wikimedia.org/wiki/File:Archives_of_American_Art_-_Patroci%C3%B1o_Barela_-_Portrait_3264.jpg');

INSERT INTO entities (id, type, name, slug, one_line_id, aliases, keywords, date_start, date_end, coordinates, cadence, portrait_image_id, bio_markdown) VALUES
(1, 'person', 'Millicent Rogers', 'millicent-rogers', "Collector who built Taos's finest archive of Native and Hispanic art.", JSON_ARRAY(), JSON_ARRAY('collector','jewelry','textiles','santos'), '1902', '1953', NULL, NULL, 1, 'Rogers came to Taos in 1947, already ill, having spent two decades collecting jewelry, textiles, and santos across the Southwest. In six years she assembled what became the core of the museum that carries her name.'),
(2, 'place', 'Taos Pueblo', 'taos-pueblo', 'Ancient Tiwa-speaking pueblo, still inhabited, on the U.S. National Register of Historic Places and UNESCO World Heritage List.', JSON_ARRAY(), JSON_ARRAY('pueblo','adobe','unesco'), NULL, NULL, '36.4358,-105.5731', NULL, 6, NULL),
(3, 'place', 'Millicent Rogers Museum', 'millicent-rogers-museum', 'Museum of Native American and Hispanic art founded on Rogers\' collection.', JSON_ARRAY(), JSON_ARRAY('museum'), '1953', NULL, '36.4614,-105.5636', NULL, 7, NULL),
(4, 'tradition', 'Natural Dye Weaving', 'natural-dye-weaving', 'Plant- and mineral-based dye recipes passed between Northern New Mexico weavers.', JSON_ARRAY(), JSON_ARRAY('weaving','textiles','dye'), NULL, NULL, NULL, 'Seasonal, tied to plant harvests', NULL, NULL),
(5, 'person', 'Ernest Blumenschein', 'ernest-blumenschein', 'Painter whose broken wagon wheel outside Taos led to the founding of an art colony in 1898.', JSON_ARRAY('Ernest L. Blumenschein'), JSON_ARRAY('taos society of artists','painter'), '1874', '1960', NULL, NULL, 2, NULL),
(6, 'person', 'Patrocinio Barela', 'patrocinio-barela', 'Santero woodcarver whose 1936 show introduced Taos carving to MoMA.', JSON_ARRAY('Patrociño Barela'), JSON_ARRAY('santero','woodcarving','wpa'), '1900', '1964', NULL, NULL, 9, NULL),
(7, 'tradition', 'Santero Woodcarving', 'santero-woodcarving', 'Devotional wood carving tradition of Northern New Mexico.', JSON_ARRAY(), JSON_ARRAY('woodcarving','santos'), NULL, NULL, NULL, 'Generational, workshop-taught', NULL, NULL),
(8, 'person', 'Bert Geer Phillips', 'bert-geer-phillips', 'Painter who stayed in Taos after a wagon wheel broke on a trip through in 1898.', JSON_ARRAY(), JSON_ARRAY('taos society of artists','painter'), '1868', '1956', NULL, NULL, 2, NULL),
(9, 'person', 'Mabel Dodge Luhan', 'mabel-dodge-luhan', 'Patron whose invitations drew D.H. Lawrence, Georgia O\'Keeffe, and Ansel Adams to Taos.', JSON_ARRAY(), JSON_ARRAY('patron','salon','writers'), '1879', '1962', NULL, NULL, 4, NULL),
(10, 'person', 'D.H. Lawrence', 'dh-lawrence', 'English novelist who lived and wrote at a ranch north of Taos in the 1920s.', JSON_ARRAY('David Herbert Lawrence'), JSON_ARRAY('novelist','writer'), '1885', '1930', NULL, NULL, NULL, NULL),
(11, 'place', 'D.H. Lawrence Ranch', 'dh-lawrence-ranch', 'Ranch roughly twenty miles northwest of Taos where D.H. Lawrence lived and wrote in the 1920s, now kept by the University of New Mexico.', JSON_ARRAY('Kiowa Ranch'), JSON_ARRAY('ranch','historic site'), NULL, NULL, NULL, NULL, 5, NULL),
(12, 'place', 'Taos, New Mexico', 'taos-new-mexico', 'Northern New Mexico town that grew an early-20th-century art colony around it.', JSON_ARRAY(), JSON_ARRAY('town'), NULL, NULL, '36.4072,-105.5734', NULL, 8, NULL);

INSERT INTO story_ideas (id, batch_id, spark_url, spark_source, spark_snippet, detected_via, matched_entity_id, proposed_new_entity, connection_note, suggested_images, suggested_links, status) VALUES
(1, '2026-08-12-run1', 'https://taosnews.com/example-dye-cooperative', 'Taos News', 'A Pueblo weaving cooperative is testing a chamisa-and-pinon-pitch yellow dye not documented in recent use.', 'rss', 1,
  NULL,
  'The recipe matches an entry underlined twice in a Millicent Rogers field notebook from 1953 — she recorded it from a weaver but never mixed it herself. The museum\'s own Navajo blanket collection is the cleanest illustration of the thread.',
  JSON_ARRAY(JSON_OBJECT('url','https://upload.wikimedia.org/wikipedia/commons/8/80/Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg','thumbnail_url','https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg/330px-Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg','source','Wikimedia Commons','license','CC BY-SA 2.0','credit_text','Navajo third-phase wearing blanket, c. 1890–95, Millicent Rogers Museum, Taos. Photo: Peter D. Tillman, CC BY-SA 2.0.','original_url','https://commons.wikimedia.org/wiki/File:Navajo_Third_phase_wearing_blanket,_circa_1890-95._Millicent_Rogers_Museum,_Taos.jpg')),
  JSON_ARRAY(JSON_OBJECT('url','https://en.wikipedia.org/wiki/Millicent_Rogers','title','Millicent Rogers — Wikipedia','type','wikipedia')),
  'published'),
(2, '2026-08-19-run1', 'https://example.org/rio-grande-gorge-trail-piece', 'Taos News', 'A local printmaker is retracing the 1898 wagon route on foot this month.', 'rss', 5,
  NULL,
  'Blumenschein\'s wheel gave out on the road into Taos in 1898 — the founding accident of the Taos art colony, and the reason Phillips stayed too. A present-day artist walking it is a clean present/past pairing.',
  JSON_ARRAY(JSON_OBJECT('url','https://upload.wikimedia.org/wikipedia/commons/6/69/Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg','thumbnail_url','https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg/330px-Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg','source','Wikimedia Commons','license','Public Domain','credit_text','Bert Phillips and Ernest Blumenschein beside the broken wagon wheel, Taos, 1898. Public domain, Wikimedia Commons.','original_url','https://commons.wikimedia.org/wiki/File:Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg')),
  JSON_ARRAY(JSON_OBJECT('url','https://en.wikipedia.org/wiki/Ernest_L._Blumenschein','title','Ernest L. Blumenschein — Wikipedia','type','wikipedia'), JSON_OBJECT('url','https://en.wikipedia.org/wiki/Bert_Geer_Phillips','title','Bert Geer Phillips — Wikipedia','type','wikipedia'), JSON_OBJECT('url','https://en.wikipedia.org/wiki/Taos_Society_of_Artists','title','Taos Society of Artists — Wikipedia','type','wikipedia')),
  'published'),
(3, '2026-08-19-run1', 'https://example.org/valdez-carving-shed', 'Taos News', 'A carving shed closed since 1991 is reopening for a winter class.', 'rss', NULL,
  JSON_OBJECT('type','person','name','A. Trujillo','one_line_id','Santero teaching a first winter carving class in his late grandfather\'s shed.','keywords', JSON_ARRAY('santero','woodcarving','valdez')),
  'New person, not yet in the entity table — proposing a record pending review rather than auto-creating one.',
  JSON_ARRAY(),
  JSON_ARRAY(),
  'new'),
(4, '2026-08-26-run1', 'https://example.org/harwood-luhan-letters', 'Taos News', 'The Harwood is remounting a small case of Mabel Dodge Luhan\'s correspondence this fall.', 'rss', 9,
  NULL,
  'Luhan\'s letters are what actually talked Lawrence into coming to New Mexico, and the ranch/manuscript trade with Frieda is a strong, well-documented throughline.',
  JSON_ARRAY(
    JSON_OBJECT('url','https://upload.wikimedia.org/wikipedia/commons/1/17/Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg','thumbnail_url','https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg/330px-Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg','source','Library of Congress','license','Public Domain','credit_text','Mabel Dodge Luhan, portrait by Carl Van Vechten. Library of Congress, Public Domain.','original_url','https://commons.wikimedia.org/wiki/File:Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg'),
    JSON_OBJECT('url','https://upload.wikimedia.org/wikipedia/commons/a/a6/Lawrence_Ranch-view_towards_Taos.JPG','thumbnail_url','https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Lawrence_Ranch-view_towards_Taos.JPG/330px-Lawrence_Ranch-view_towards_Taos.JPG','source','Wikimedia Commons','license','CC0','credit_text','D.H. Lawrence Ranch, view toward Taos. Photo: Vivaverdi, CC0.','original_url','https://commons.wikimedia.org/wiki/File:Lawrence_Ranch-view_towards_Taos.JPG')
  ),
  JSON_ARRAY(JSON_OBJECT('url','https://en.wikipedia.org/wiki/Mabel_Dodge_Luhan','title','Mabel Dodge Luhan — Wikipedia','type','wikipedia'), JSON_OBJECT('url','https://en.wikipedia.org/wiki/D._H._Lawrence','title','D. H. Lawrence — Wikipedia','type','wikipedia'), JSON_OBJECT('url','https://en.wikipedia.org/wiki/D._H._Lawrence_Ranch','title','D. H. Lawrence Ranch — Wikipedia','type','wikipedia')),
  'published');

INSERT INTO stories (id, story_idea_id, title, dek, slug, body_markdown, spark_url, spark_source, primary_entity_id, status, published_at) VALUES
(1, 1, 'The Recipe That Skipped a Generation',
  'A Pueblo dye house is testing a chamisa-and-pinon-pitch yellow that hasn\'t been mixed in living memory — pulled from a notebook in the Millicent Rogers archive.',
  'the-recipe-that-skipped-a-generation',
  'On a Tuesday in June, three women at the Pueblo weaving cooperative set a pot of chamisa flowers to boil, then added crushed pinon pitch on a hunch. The yellow that came up was not the yellow they were expecting — warmer, closer to something none of them had actually seen dyed into wool before.\n\nThe same combination turns up, underlined twice, in a field notebook Millicent Rogers kept in her last years collecting in Taos. She never mixed the dye herself — she recorded it from a weaver whose name she wrote down and then, in the next line, apologized for possibly spelling wrong.\n\n![Navajo third-phase wearing blanket, c. 1890-95, Millicent Rogers Museum](https://upload.wikimedia.org/wikipedia/commons/8/80/Navajo_Third_phase_wearing_blanket%2C_circa_1890-95._Millicent_Rogers_Museum%2C_Taos.jpg)\n\n*Navajo third-phase wearing blanket, c. 1890–95, Millicent Rogers Museum, Taos. Photo: Peter D. Tillman, CC BY-SA 2.0.*\n\nSeventy-three years separate the notebook from the pot. What ties them is not the recipe alone but the fact that it was written down at all — and that someone, this summer, went looking for the notebook instead of guessing.\n\nThe cooperative is dyeing a full run this month. If the color holds through washing, it will go into a blanket already promised to the museum — a small return delivery, seventy-three years late.',
  'https://taosnews.com/example-dye-cooperative', 'Taos News', 4, 'published', '2026-08-12 09:00:00'),
(2, 2, 'A Wheel Breaks, a Colony Begins',
  'A broken wagon wheel stranded two painters outside Taos in 1898 — and neither of them really left.',
  'a-wheel-breaks-a-colony-begins',
  'A local printmaker is retracing the 1898 wagon route on foot this month, starting from the same stretch of road north of town where a wheel gave out and changed two painters\' plans.\n\nIn the spring of 1898, [Ernest L. Blumenschein](https://en.wikipedia.org/wiki/Ernest_L._Blumenschein) convinced his studio-mate [Bert Geer Phillips](https://en.wikipedia.org/wiki/Bert_Geer_Phillips) to join him on a sketching trip west. They outfitted a wagon in Denver and set out for Mexico. On the rough road through northern New Mexico, a wagon wheel broke. Blumenschein rode the wheel into the nearest town — [Taos](https://en.wikipedia.org/wiki/Taos,_New_Mexico) — to have it repaired, leaving Phillips alone with the wagon for three days.\n\n![Bert Phillips and Ernest Blumenschein beside the broken wagon wheel, Taos, 1898](https://upload.wikimedia.org/wikipedia/commons/6/69/Broken_Wagon_Wheel_-_Taos_1898_Phillips_and_Blumenschein.jpg)\n\n*Bert Phillips and Ernest Blumenschein beside the broken wagon wheel, Taos, 1898. Public domain, Wikimedia Commons.*\n\nNeither painter made it to Mexico. They sold the wagon, set up a studio, and stayed. By 1915 they were among the founders of the [Taos Society of Artists](https://en.wikipedia.org/wiki/Taos_Society_of_Artists), the group historians credit with starting the Taos art colony.\n\nThe road the wheel broke on is still, more or less, the way most people arrive in Taos.',
  'https://example.org/rio-grande-gorge-trail-piece', 'Taos News', 5, 'published', '2026-08-19 09:00:00'),
(3, 4, 'Mabel Dodge Luhan\'s Salon',
  'A patron\'s invitation brought a novelist to a ranch north of Taos — and a manuscript changed hands to keep him there.',
  'mabel-dodge-luhans-salon',
  'The Harwood is remounting a small case of Mabel Dodge Luhan\'s correspondence this fall — letters that once talked a novelist into moving to New Mexico.\n\n[Mabel Dodge Luhan](https://en.wikipedia.org/wiki/Mabel_Dodge_Luhan) settled in Taos in 1917 and spent the next four decades inviting writers and artists to see what she saw in it. [D.H. Lawrence](https://en.wikipedia.org/wiki/D._H._Lawrence) accepted an invitation from her and arrived with his wife Frieda in 1922; Georgia O\'Keeffe and Ansel Adams passed through the same circle in the years after.\n\n![Mabel Dodge Luhan, portrait by Carl Van Vechten](https://upload.wikimedia.org/wikipedia/commons/1/17/Portrait_of_Mabel_Dodge_Luhan_LCCN2004663225.jpg)\n\n*Mabel Dodge Luhan, portrait by Carl Van Vechten. Library of Congress, Public Domain.*\n\nIn 1924, hoping to keep the Lawrences in New Mexico, Luhan offered them a 160-acre ranch north of town. Lawrence refused the gift outright — \"we can\'t accept such a present from anybody,\" he said — but Frieda accepted on one condition: they would give Mabel the manuscript of Lawrence\'s novel *Sons and Lovers* in exchange. The deed went in Frieda\'s name.\n\n![D.H. Lawrence Ranch, view toward Taos](https://upload.wikimedia.org/wikipedia/commons/a/a6/Lawrence_Ranch-view_towards_Taos.JPG)\n\n*D.H. Lawrence Ranch, view toward Taos. Photo: Vivaverdi, CC0.*\n\nA manuscript for a mountainside: the ranch still carries Lawrence\'s name, decades after the trade that paid for it.',
  'https://example.org/harwood-luhan-letters', 'Taos News', 9, 'published', '2026-08-26 09:00:00');

INSERT INTO story_entities (story_id, entity_id) VALUES
(1,1), (1,3), (1,4),
(2,5), (2,8), (2,12),
(3,9), (3,10), (3,11);

INSERT INTO story_images (story_id, image_id, sort_order) VALUES
(1,7,1),
(2,2,1),
(3,4,1), (3,5,2);
