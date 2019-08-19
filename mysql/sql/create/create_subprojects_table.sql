CREATE TABLE subprojects (
  sidora_subproject_id VARCHAR(45) NOT NULL COMMENT 'PID for specific Subproject in Database',
  ct_subproject_id VARCHAR(45) NOT NULL COMMENT 'Camera Trap Subproject ID',
  name VARCHAR(45) NOT NULL COMMENT 'Name of Subproject',
  sidora_project_id VARCHAR(45) NOT NULL COMMENT 'ID of Parent Project',
  PRIMARY KEY (sidora_subproject_id),
  FOREIGN KEY (sidora_project_id) REFERENCES projects (sidora_project_id))
