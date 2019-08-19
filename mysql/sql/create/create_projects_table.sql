CREATE TABLE projects (
  sidora_project_id VARCHAR(45) NOT NULL COMMENT 'Project PID from SIdora',
  ct_project_id VARCHAR(45) NOT NULL COMMENT 'Camera Trap Project ID ',
  name VARCHAR(45) NOT NULL COMMENT 'Name of Project',
  lon VARCHAR(45) NOT NULL COMMENT 'Longitude of Project',
  lat VARCHAR(45) NOT NULL COMMENT 'Latitude of Project',
  publish_date VARCHAR(45) NOT NULL COMMENT 'Publish date of Project',
  summary VARCHAR(45) NULL COMMENT 'Summary of Camera Trap Project',
  PRIMARY KEY (sidora_project_id))
