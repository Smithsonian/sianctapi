CREATE TABLE plots (
  sidora_plot_id VARCHAR(45) NOT NULL COMMENT 'PID for specific Plot in Database',
  name VARCHAR(45) NOT NULL COMMENT 'Name of plot for scientific purposes or whatever (also called Other Treatment)',
  treatment VARCHAR(45) NULL COMMENT 'Description of what the plot is (also called Other Treatment Description)',
  sidora_subproject_id VARCHAR(45) NOT NULL COMMENT 'Camera Trap Subproject ID of Parent Subproject for Plot',
  PRIMARY KEY (sidora_plot_id),
  FOREIGN KEY (sidora_subproject_id) REFERENCES subprojects (sidora_subproject_id))
