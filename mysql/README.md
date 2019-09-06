# SIANCT MySQL Cache Migration Module

## Overview

The SIANCT MySQL Cache Migration Module allows for the transfer of Sidora data
stored in Fedora to a MySQL database. This MySQL database is intended to replace
the current text file cache used by SIANCT.

This module contains a script that can be run on the command line with optional
user provided parameters as well as a mysql data populator class that handles the
parsing and transference of data from Fedora to a MySQL relational database.

In addition, this module uses composer to import the Easy_RDF library.

The module contains a directory for SQL scripts used to create the database and
data tables and a log directory.

## Usage Instructions

The `sianct_mysql_rebuild` script can be run on the command line with or without
user provided parameters:

`php sianct_mysql_rebuild.php`

Run without parameters, the script will start with the root object of a Fedora
repository (this fedora repository is configured in sianct.ini). It will then
work recursively, finding each fedora object from projects down to deployments,
parsing necessary data from each object and inserting into the appropriate
data table within the database.

Once finished, the script will output a log file indicating whether the data
population process was successful, how long the process took, and a log of
error and debug messages. In addition, if a fedora object cannot be added to
the database, its PID is recorded in a list that is saved to the log directory.

The user can also run the script with several optional parameters. A brief overview
of these parameters is listed below.

### Overview of optional parameters:

* -d | --database : Specifies database information. Format - host:username:password:databasename
* -f | --file     : Specifies path to a file containing a list of fedora pids populate in the database
* -p | --pids     : A list of pids separated by commas. Format - test.pid:1,test.pid:2,test.pid:3 (no spaces)
* -r | --rebuild  : If rebuild option flag set to true, the whole database is dropped and rebuilt. Format - TRUE:FALSE

#### database parameter:

The user can specify a host, username, password, and database name for the script to
use. This information is provided in the following format:

`<host>:<username>:<password>:<databasename>`

If the user does not provide database information or provides incomplete or
incorrectly formatted database information, the script will default to the
database information provided in sianct.ini.

#### file and pids parameters:

The user can provide a list of pids for the script to recurse through in place of
the root pid. These pids can either be provided as a comma separated list (no spaces)
or in the form of a file path. This file must be formatted such that there is one
pid per line.

If the user provides a comma separated list of pids **and** a file path, both
parameters are processed and merged into a single array of pids with duplicate pids
removed from the list.  

#### rebuild parameter:

The rebuild parameter is a boolean (**TRUE** or **FALSE**). This parameter is by
default set to false. When set to true, the currently existing database is deleted
and rebuilt from scratch.

The script is designed to be re-run on the same database without issue, however.
If new information needs to be ingested into the database or if an error occurred
during initial population, the script can be re-run without the rebuild parameter set
to **TRUE**.

Use this parameter in case the database has been significantly damaged or otherwise
rendered unusable.

## Configuration

The SIANCT MySQL Cache Migration Module contains configuration options for:

* database
* sql
* fedora

### Database:

- **host**: The hostname or IP address of the configured mysql database  
- **user**: The username of designated user credentials for operating on the database
- **pass**: The password of designated user credentials for operating on the database
- **dbname**: The name of the MySQL database

### SQL:

- **projects**: Path to SQL script for creating projects data table
- **subprojects**: Path to SQL script for creating subprojects data table
- **plots**: Path to SQL script for creating plots data table
- **deployments**: Path to SQL script for creating deployments data table
- **species**: Path to SQL script for creating species data table
- **observations**: Path to SQL script for creating observations data table

### fedora:

- **fedorahost**: The hostname or IP address of the configured fedora repository
- **fedoraport**: The port the configured fedora repository runs on
- **fedorauserpass**: Username and Password credentials for fedora repository Format: (<username>:<password>)
