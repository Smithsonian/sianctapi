CREATE TABLE deployments (
  sidora_deployment_id VARCHAR(45) NOT NULL COMMENT 'PID for specific Deployment in Database',
  ct_deployment_id VARCHAR(45) NOT NULL COMMENT 'Camera Trap Deployment ID',
  name VARCHAR(45) NOT NULL COMMENT 'Name of deployment',
  sidora_subproject_id VARCHAR(45) NOT NULL COMMENT 'PID of Parent Subproject',
  access_constraints VARCHAR(45) NULL COMMENT 'Federation data use policy, and may also include image use constraints (including embargos). Values include “NE”, “NT”, and “none”',
  feature_type VARCHAR(45) NOT NULL COMMENT 'Comma separated list of values pertaining to deployment location',
  feature_description VARCHAR(45) NULL COMMENT 'Description of deployment location ',
  bait_type VARCHAR(45) NULL COMMENT 'Type of bait used in Camera Trap Deployment',
  bait_description VARCHAR(45) NULL COMMENT 'Description details regarding bait used in Camera Trap Deployment',
  sidora_plot_id VARCHAR(45) NULL COMMENT 'Make this part of the primary key in case there are multiple plots per deployment ',
  camera_id VARCHAR(45) NULL COMMENT 'User-filled ID for the camera put out',
  proposed_lat VARCHAR(45) NULL COMMENT 'Proposed latitude value of Camera Trap Deployment',
  proposed_lon VARCHAR(45) NULL COMMENT 'Proposed longitude value of Camera Trap Deployment',
  actual_lat VARCHAR(45) NOT NULL COMMENT 'Actual latitude value of Camera Trap Deployment',
  actual_lon VARCHAR(45) NOT NULL COMMENT 'Actual longitude value of Camera Trap Deployment',
  camera_failure_details VARCHAR(45) NOT NULL COMMENT 'Details regarding Camera failure for deployment if failure has occurred, or `Camera Functioning` if success',
  detection_distance INT NOT NULL COMMENT 'The farthest distance the camera can physically detect movement or activity. Limited by camera make/model and by surrounding environment',
  sensitivity_setting VARCHAR(45) NULL COMMENT 'Settings for deployment camera trap sensitivity',
  quiet_period_setting VARCHAR(45) NULL COMMENT 'Number of seconds of the quiet period between camera trigger events (i.e. 60 for a 1 minute delay). Zero is a valid value. Only numbers in the field',
  image_resolution_setting VARCHAR(45) NULL COMMENT 'Resolution of daytime photos in megapixels (i.e. 3.1).',
  deployment_notes VARCHAR(45) NULL COMMENT 'Any additional comments about the deployment',
  PRIMARY KEY (sidora_deployment_id, ct_deployment_id),
  FOREIGN KEY (sidora_subproject_id) REFERENCES subprojects (sidora_subproject_id))
