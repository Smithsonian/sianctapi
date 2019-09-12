CREATE TABLE projects (
  sidora_project_id VARCHAR(256) NOT NULL COMMENT 'Project PID from SIdora',
  ct_project_id VARCHAR(256) NOT NULL COMMENT 'Camera Trap Project ID ',
  name VARCHAR(256) NOT NULL COMMENT 'Name of Project',
  lon VARCHAR(256) NOT NULL COMMENT 'Longitude of Project',
  lat VARCHAR(256) NOT NULL COMMENT 'Latitude of Project',
  publish_date VARCHAR(256) NOT NULL COMMENT 'Publish date of Project',
  objectives VARCHAR(5000) NULL COMMENT 'Project Objectives',
  data_constraints VARCHAR(256) NULL COMMENT 'Project data constraints',
  owner VARCHAR(256) NULL COMMENT 'Project owner',
  email VARCHAR(256) NULL COMMENT 'Project email contact',
  principal_investigator VARCHAR(256) NULL COMMENT 'Project principal investigator',
  country_code VARCHAR(256) NULL COMMENT 'Country code for project',
  PRIMARY KEY (sidora_project_id))
