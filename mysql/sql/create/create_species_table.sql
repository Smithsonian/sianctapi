CREATE TABLE species (
  iucn_id INT NOT NULL COMMENT 'Species ID from the International Union for Conservation of Nature',
  tsn_id INT NOT NULL,
  iucn_status VARCHAR(45) NOT NULL COMMENT 'Conservation status of species set by the International Union for Conservation of Nature',
  scientific_name VARCHAR(256) NOT NULL COMMENT 'Species name for animal IDed in sequence',
  common_name VARCHAR(256) NOT NULL COMMENT 'Common name for animal IDed in sequence',
  PRIMARY KEY (iucn_id))
