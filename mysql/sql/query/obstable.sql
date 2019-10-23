SELECT
observations.obstable_id AS "obstable",
deployments.sidora_plot_id AS "plot",
projects.name AS "project",
subprojects.name AS "subproject",
deployments.name AS "deploymentName",
observations.id_type AS "idType",
deployments.ct_deployment_id AS "deployId",
observations.sequence_id AS "sequenceId",
observations.begin_time AS "beginTime",
observations.end_time AS "endTime",
species.scientific_name AS "speciesName",
species.common_name AS "commonName",
observations.age AS "age",
observations.sex AS "sex",
observations.individual AS "individual",
observations.count AS "count",
deployments.actual_lat AS "actualLat",
deployments.actual_lon AS "actualLon",
deployments.feature_type AS "featureType",
projects.publish_date AS "publishDate",
projects.lat AS "projectLat",
projects.lon AS "projectLon",
deployments.access_constraints AS "accessConstraints"
FROM sianctapi.observations, sianctapi.deployments, sianctapi.subprojects, sianctapi.projects, sianctapi.species
WHERE
observations.sidora_deployment_id = deployments.sidora_deployment_id
AND deployments.sidora_subproject_id = subprojects.sidora_subproject_id
AND subprojects.sidora_project_id = projects.sidora_project_id
AND observations.iucn_id = species.iucn_id
