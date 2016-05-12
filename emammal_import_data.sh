#!/bin/bash
#
# Import data into the eMammal Drupal site through exposed endpoints.
#
set -e


#####################################
# Authenticated curl request wrapper
#
# Globals:
#   TOKEN
# Arguments
#   None
# Returns
#   None
#####################################
auth_curl() {
  curl -X POST \
    -d "auth_token=${TOKEN}" \
    "$@"
}

#####################################
# Curl wrapper for easy host access
#
# Globals:
#   HOST
# Arguments
#   None
# Returns
#   None
#####################################
curl() {
  local -a args
  local exitcode
  local i

  # If required, append hostname header
  if [[ "${HOST:-xxx}" != "xxx" ]]; then
    args+=( -H "Host: ${HOST}" )
  fi

  for i in "${@}"; do
    args+=( "${i}" )
  done

  set +e
  command curl -f "${args[@]}"
  exitcode="$?"

  if [[ "${exitcode}" -ne 0 ]]; then
    echo "Curl request failed with exit code ${exitcode}." >&2
    echo "Arguments: " >&2
    for i in "${args[@]}"; do
      echo "	${i}" >&2
    done
  fi
  set -e
}

###########################################
# Access the AWS favorite photos endpoint
#
# Globals:
#   URL
# Arguments
#   None
# Returns
#   None
###########################################
get_aws_favorite_photos() {
  auth_curl -s \
    -d command=efav_import \
    "${URL}/drush_scripts/index.php"
}


#########################################
# Access an eMammal EDA import endpoint
#
# Globals
#   URL
# Arguments
#   String: EDA import type
# Returns
#   None
#########################################
get_eda_import() {
  local limit
  local type
  type="$1"
  limit="${2:-}"

  auth_curl -s \
    -d command=eda_import \
    -d arguments="{\"type\":\"${type}\"}" \
    ${limit:+-d options="{\"limit\":\"${limit}\"}"} \
    "${URL}/drush_scripts/index.php"
}


##################################################################
# Access and process the eMammal EDA import endpoint for species
#
# Arguments:
#   None
# Returns:
#   None
##################################################################
get_eda_import_species() {
  local counter
  local increment
  local total

  increment=50
  total="$(get_eda_import count)"

  if [[ "${total:--1}" -lt 1 ]]; then
    echo "Invalid deployment count" >&2
    return
  else
    counter=0
    while [[ ${counter} -lt ${total} ]]; do
      get_eda_import species "${increment}"
      (( counter += "${increment}" ))
      echo "${counter} ${total}" | awk '{printf "Processed %.2f%%\n", $1 / $2 * 100}'
    done
  fi

  echo "Finished processing species"
}


#########################################
# Print a nicely distinguishable header
#
# Arguments
#   String: Header
# Returns
#   None
#########################################
header() {
  local char
  local header

  char='='
  header="$1"

  printf "\n\n%s\n" "${header}"
  printf "%$((${#header}))s\n" | tr ' ' "${char}"
}


######################################################################
# Update data for a specified type
#
# If new data sources are added, the all case option must be updated
#
# Arguments
#   None
# Returns
#   None
######################################################################
update_data() {
  local i
  local type

  type="$1"

  case "${type}" in
    all)
      for i in deployments species plot-data summary favorite-photos; do
        update_data "${i}"
      done
      ;;
    deployments)
      header "Deployments"
      get_eda_import deployments
      ;;
    species)
      header "Species"
      get_eda_import_species
      ;;
    plot-data)
      header "Project Data"
      get_eda_import plot-data
      ;;
    summary)
      header "Project Summary"
      get_eda_import summary
      ;;
    favorite-photos)
      header "AWS Favorite Photos"
      get_aws_favorite_photos
      ;;
    *)
      echo "Invalid type: ${type}" >&2
      exit 1
  esac

}


###############
# Basic usage
#
# Arguments:
#   None
# Returns:
#   None
###############
usage() {
  cat >&2 <<EOF
Usage: $(basename "$0") [-h] [-n <hostname> ] [-t <type> ] <emammal site url> <token>

Options:
	-h
		Shows usage text.
	-n <hostname>
		Specify a host header for all curl requests.
	-t <type>
		Specify the data type to update. Default is all data.
		Valid types: all, deployments, species, plot-data, summary, favorite-photos
EOF
}


################################################
# Test if a HTTP response code URL returns 200
#
# Arguments:
#   String, URL
# Returns:
#   Integer, exit code
################################################
valid_url() {
  local url
  url="$1"

  curl -sI "${url}" \
    | head -n1 \
    | egrep -s '^HTTP/1\.. 200 OK' >/dev/null

  return $?
}


####################
# Main
#
# Globals:
#   HOST
#   TOKEN
#   URL
# Arguments:
#   String, eMammal site root's URL
#   String, Drush authentication token
# Returns:
#   None
####################
main() {
  local type

  HOST=''
  TOKEN=''
  URL=''

  type="all"

  while getopts ":hn:t:" opt; do
    case "${opt}" in
      h)
        usage
        exit
        ;;
      n)
        HOST="${OPTARG}"
        ;;
      t)
        type="${OPTARG}"
        ;;
      \?)
        echo "Invalid option: -${OPTARG}" >&2
        usage
        exit 1
        ;;
      :)
        echo "Option: -${OPTARG} requires an argument." >&2
        exit 1
        ;;
    esac
  done
  shift "$((OPTIND-1))"

  if [[ $# -lt 2 ]]; then
    usage
    exit 1
  fi

  URL="${1%/}"
  TOKEN="${2}"

  readonly URL
  readonly HOST
  readonly TOKEN

  # Verify that the URL is reachable
  if ! valid_url "${URL}"; then
    echo "Could not reach ${URL}" >&2
    exit 1
  fi

  # Update data
  update_data "${type}"

  echo "$(basename "$0") completed"
}
main "$@"
