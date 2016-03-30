#!/bin/bash
clear
########################################################################
# This script will process the species import by looping through a set #
# number of deployment per cycle.                                      #
########################################################################

SCRIPT_URL="http://emammal.dev.si.edu/drush_scripts/?command=eda_import"
AWS_SCRIPT_URL="http://emammal.dev.si.edu/drush_efav_import"

#################
## DEPLOYMENTS ##
#################
URL="$SCRIPT_URL&arguments={\"type\":\"deployments\"}"
echo "Executing DEPLOYMENTS update"
RESULT="`wget -qO- $URL`"
echo $RESULT

#############
## SPECIES ##
#############
echo "Executing SPECIES update"

# Retrieve number of deployments.
# We need to chunk species because it takes FOR-EVER!
COUNT_URL="$SCRIPT_URL&arguments={\"type\":\"count\"}"
NUM_DEPLOYMENTS="`wget -qO- $COUNT_URL`"

# Loop through cURL requests until the count = 0
i="0"
while [ $i -lt ${NUM_DEPLOYMENTS} ]
do
  URL="$SCRIPT_URL&arguments={\"type\":\"species\",\"limit\":\"250\"}"
  echo $URL
  RESULT="`wget -qO- $URL`"
  echo $RESULT
  i=$[$i+250]
  echo "$i of $NUM_DEPLOYMENTS deployments processed"
done

##################
## PROJECT DATA ##
##################
URL="$SCRIPT_URL&arguments={\"type\":\"plot-data\"}"
echo "Executing PROJECT DATA update"
RESULT="`wget -qO- $URL`"
echo $RESULT

###################
## AWS FAVORITES ##
###################
URL="$AWS_SCRIPT_URL"
echo "Executing AWS FAVORITES update"
RESULT="`wget -qO- $URL`"
echo $RESULT


echo "##### DONE #####"
