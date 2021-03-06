# Code to create a capture history (i.e., detection history) from camera trap 
# images stored on eMammal servers.
# Data input is from user defined selections on eMammal's user interface


### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### 

#### PREAMBLE ####


# Read in eMammal Inputs from API using commandArgs() and store object as `args`
args <- commandArgs(TRUE)

# object containing animal observations
csvFileName <- args[1]
csvFile<-read.csv(csvFileName)
#csvFile <- read.csv("sianctapi-selected-observations-5c5f7749e8e6d.csv")

# remove spaces in variable names and replace with "."
names(csvFile) <- gsub(" ", ".", names(csvFile))

# object containing deployment metadata
depcsvFileName <- args[2]
depcsvFile<-read.csv(depcsvFileName)
#depcsvFile <- read.csv("deployment_metadata_20190209195846.csv")

# number of days for interval, defined by the user
clump <- args[3]
#clump <- "7"
    
# change class of clump object and convert to seconds
clump <- as(clump, 'double')*60*60*24

# capture history to be returned to user
resultFile <- args[4]
#resultFile <- "test.csv"

# libraries needed to execute script

require(reshape2)
require(lubridate)
require(reshape)

# reset system time zone
Sys.setenv(TZ = 'GMT')


# END PREAMBLE 


### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### 


#### FUNCTIONS ####

FixTimeStamps <- function(obs, metadata) {
  # This function standardizes time stamps to be of class(POSIXCT) with same format.
  # In the process, the function removes data that are missing dates, 
  # removes the "T" from the Begin.Time and End.Time columns, and removes data with
  # erroneous data (timestamps from the future, default timestamps, etc)
  #
  # Args:
  #   obs: animal observations from eMammal (object stored as `csvFile`).
  #   metadata: camera deployment metadata from eMammal (object `depcsvFile`).
  #
  # returns:
  #   `csvFile` updated with time columns standardized and reformatted.
  #   `depcsvFile` updated with time columsn standardized and reformatted.
  
  # if column name is Deployment.ID change it to Deploy.ID
  if("Deployment.ID" %in% colnames(obs)){
    colnames(obs)[which(colnames(obs) == "Deployment.ID")] <- "Deploy.ID"
  }
  
  # Remove any data with no dates
  obs <- obs[!(obs$Begin.Time == "" | is.na(obs$Begin.Time)), ]
  
  # notify user of missing dates in depcsvFile
  # `missing` is a character object containing deployment_ids with no dates
  missing <- as.character(metadata[which(metadata$actual_date_out == "" | 
                                             is.na(metadata$actual_date_out)), ]$deployment_id)
  
  # the formatting in the following if message is messy, but necessary to print correctly
  if(length(missing) > 0) {
    warningmsg<<-warning(sprintf("Some deployments were missing dates and those deployments were removed.
Here are the deployments that were removed: %s", paste(missing, collapse = " ")),
            " ", # adds space between missing deployments and contact message
            "\nContact eMammal with this warning message and the list of deployments for assistance: eMammal@si.edu", call. = F)
  }
  else {
    warningmsg<<-NA
  }
  
  # remove rows with no dates in metadata
  metadata <- metadata[!(metadata$actual_date_out == "" | is.na(metadata$actual_date_out)), ]
  
  # double check that obs doesn't contain the deployment_ids in missing
  if (length(missing) > 0){
    obs <- obs[!(obs$Deploy.ID %in% missing),]}
  
  #notify user of issues in csvFile - missingAnimals means the data is weird
  missingAnimals <- as.character(metadata[which(obs$Common.Name == "" | 
                                           is.na(obs$Common.Name)), ]$deployment_id)
  
  # the formatting in the following if message is messy, but necessary to print correctly
  if(length(missingAnimals) > 0) {
    warningmsg1<<-warning(sprintf("Some deployments were missing dates and those deployments were removed.
Here are the deployments that were removed: %s", paste(missingAnimals, collapse = " ")),
                         " ", # adds space between missing deployments and contact message
                         "\nContact eMammal with this warning message and the list of deployments for assistance: eMammal@si.edu", call. = F)
  }
  else {
    warningmsg1<<-NA
  }
  
  # remove rows with missing Animals in obs
  obs <- obs[!(obs$Common.Name == "" | is.na(obs$Common.Name)), ]
  
  #Replace the Ts in the timestamps with a space
  obs[c("Begin.Time", "End.Time")] <- lapply(obs[c("Begin.Time", "End.Time")], 
                                             function(x) gsub("T", " ", x))
  
  # make a copy of what the Begin.Time and End.Times are to start with 
  #(will be used in a moment to check that the dates were all converted to correct format)
  BeginEnd <- obs[c("Begin.Time", "End.Time")]
  
  #Format the times columns as class POSIXct
  obs[c("Begin.Time", "End.Time")] <- lapply(obs[c("Begin.Time", "End.Time")], 
                                             function(x) as.POSIXct(x, format = "%Y-%m-%d %H:%M:%S"))

  metadata[c("actual_date_out", "retrieval_date")] <- lapply(metadata[c("actual_date_out", "retrieval_date")], 
                                             function(x) as.POSIXct(x, format = "%Y-%m-%d")) 
  
  # check that it successfully converted all the formats in R
  if(identical(is.na(BeginEnd), is.na(obs[c("Begin.Time", "End.Time")])) == FALSE){
    warning("Begin and End times are not all in the correct format. In the selected Observations csv please make sure the Begin.Time and End.Time columns are one of following formats: '%Y-%m-%d %H:%M:%S' or  '%Y-%m-%dT%H:%M:%S'
            To do this: (1) open the selected observations csv in excel (2) Select the Begin and End Time columns (3) Once selected, right click on the selected cells and select 'format cells' (4) under the 'number' cell, press custom and paste 'yyyy-mm-ddThh:mm:ss' (no quotation marks) in the space under 'Type' (5) hit ok (6) save and reload the csv to this R script")
  }
  
  # Remove any entries from the future
  obs <- obs[!(obs$Begin.Time > Sys.Date()),]
  
  #Remove any timestamps where the time was reset to the factory default
  #Define the factory default times to find them easily
  times <- c("2000-01-01 00:00:00", "2011-01-01 00:00:00", 
             "2012-01-01 00:00:00", "2013-01-01 00:00:00", 
             "2014-01-01 00:00:00", "2015-01-01 00:00:00", 
             "2016-01-01 00:00:00", "2017-01-01 00:00:00", 
             "2018-01-01 00:00:00", "2019-01-01 00:00:00", 
             "2020-01-01 00:00:00", "2021-01-01 00:00:00")
  
  obs <- obs[!(obs$Begin.Time %in% times),]
  
  # update csvFile and depcsvFile
  csvFile <<- obs
  depcsvFile <<- metadata
}


CalculateSamplePeriod <- function(obs, metadata) {
  # This function calculates which sample period (i.e., "visit") the camera observation
  # from csvFile occurs in. The first step is to calculate total sampling length 
  # for each deployment. Then identify any mismatch in metadata and obs dates. 
  # If mismatch exists (object badDates), remove cameras and notify user. 
  #
  # Args:
  #   obs: animal observations from eMammal (object stored as `csvFile`).
  #   metadata: camera deployment metadata from eMammal (object `depcsvFile`).
  #
  # returns:
  #   `SamplePeriod` with two columns: 1. Deploy.ID - unique identifier for deployments
  #                                    2. SamplePeriod -  which 'visits' the 
  #                                                       species was detected
  
  # make sure R recognizes dates as dates
  metadata$retrieval_date <- ymd(metadata$retrieval_date)
  metadata$actual_date_out <- ymd(metadata$actual_date_out)
  
  # Calculate total sampling length in days for each deployment
  metadata$totalSamplingPeriod <- difftime(metadata$retrieval_date, 
                                           metadata$actual_date_out, 
                                           units = "days")
  # Merge obs and metadata
  tmp <- merge(obs, 
               metadata[, c("deployment_id","actual_date_out","retrieval_date", 'totalSamplingPeriod')],
               by.x = "Deploy.ID",
               by.y = "deployment_id",
               all.x = T)
  
  # identify which deployments have mismatch in obs and metadata dates AND nothing in the date column
  badDates <- as.character(unique(
    tmp[c(which(as.Date(tmp$End.Time, format = "%Y-%m-%d") > tmp$retrieval_date), 
          which(is.na(tmp$End.Time))),]$Deploy.ID))
  
  # notify user
  # the following if statement has messy formatting but is neccessary to print correctly. Please do not edit. 
  if(length(badDates) > 0) {
    warningmsg2<<-warning(sprintf("Some deployments had a mismatch in observation and camera metadata dates and were removed.
                    Here are the deployments that were removed: %s", paste(badDates, collapse = " ")),
            " ",
            "\nContact eMammal with this warning message and the list of deployments for assistance: eMammal@si.edu", call. = F)
  }
  else {
    warningmsg2<<-NA
  }
  # remove badDates
  tmp <- tmp[!(tmp$Deploy.ID %in% badDates),]
  
  # convert column class
  tmp$Deployment.Name <- as.character(tmp$Deployment.Name)
  
  # create empty variable to hold loop output
  tmp$SamplePeriod <- NA
  
  # remove empty rows
  tmp <- tmp[rowSums(is.na(tmp)) != ncol(tmp),]
  
  # loop to update SamplePeriod
  for (i in unique(tmp$Deployment.Name)){
    # subset to individual Deployment.Name
    holder <- subset(tmp, Deployment.Name == i)
    
    tmp[tmp$Deployment.Name == i,]$SamplePeriod <- cut(holder$Begin.Time,
                                                   breaks = as.POSIXct(seq(from = min(holder$Begin.Time, na.rm = T),
                                                                           by = clump,
                                                                           to = max(holder$End.Time, na.rm = T) + clump)), labels = F)
    rm(holder)
  }
  
  # reduce tmp down to two columns: Deploy.ID and SamplePeriod
  tmp <- subset(tmp, select = c('Deploy.ID', 'SamplePeriod'))
  
  # find deployments in metadata that did not detect focal species and add to the
  # data frame containing sites where species was detected
  tmp2 <- as.character(metadata[metadata$deployment_id %in% setdiff(metadata$deployment_id,tmp$Deploy.ID),]$deployment_id)
  tmp2 <- data.frame(Deploy.ID = tmp2, SamplePeriod = rep(0, length(tmp2)))
  
  tmp3 <- rbind(tmp, tmp2)
  
  # order by deployment date
  tmp3 <- tmp3[order(tmp3$Deploy.ID),]
  
  # add to the global environment
  samplePeriod <<- tmp3
  
}


CreateCaptureHistory <- function(samplePeriod) {
  # This function creates a capture history from eMammal camera observations. 
  #
  # Args:
  #   obs: A two column data frame where the first column is the deployment ID 
  #        and the second column indicates during which sampling period the camera 
  #        occurred observation. The object created from the function CalculateSamplePeriod is ideal.
  #
  # Returns:
  #   CapHist: A data frame containing a camera-site specific (i.e., deploy.ID)
  #            capture history for the focal species. 
  
  # Reshape the data using melt
  transform <- melt(samplePeriod, id.vars="Deploy.ID")
  pivot <- dcast(transform, Deploy.ID ~ value, fun.aggregate = length)
  
  # Check that all sample period are included (even those when no animals are detected)
  allPeriods <- as.character(seq(min(samplePeriod$SamplePeriod), max(samplePeriod$SamplePeriod)))
  missingColumns <- allPeriods[allPeriods %in% colnames(pivot) == FALSE]
  pivot[,missingColumns] <- 0
  pivot <- pivot[,c("Deploy.ID",as.character(sort(as.numeric(colnames(pivot[which(colnames(pivot) != "Deploy.ID")])))))]
  
  # Remove column indicating whether species was detected 
  pivot<- pivot[ -c(2) ] 
  
  # Set NAs to non-detection status
  pivot[is.na(pivot)]=0
  
  #Turn all non-zero matrix elements for SamplePeriod into 1
  pivot[,2:ncol(pivot)][pivot[,2:ncol(pivot)] != 0] = 1
  
  #insert identifying pieces of information in pivot2
  cameras_dates <- depcsvFile[,c("deployment_id","actual_date_out","retrieval_date")] 
  colnames(cameras_dates) <- c("Deploy.ID", "Start.Date","End.Date")
  species_name=unique(csvFile$Common.Name)
  cameras_dates$Common.Name<-species_name
  cameras_dates$ClumpNum<-clump/(60*60*24)
  
  #store in environment
  CapHist<- merge(cameras_dates,pivot, by="Deploy.ID", all.x=TRUE)
  
  #CapHist <<- pivot
  
  #notify user of status
  if(any(is.na(CapHist))) {
    warning('There was an error creating the Capture History. Please email eMammal at eMammal@si.edu with this warning message and the inputs at each step above.', call. = F)
  } else {
    if (!is.na(warningmsg))
      CapHist[nrow(CapHist)+1,"ClumpNum"] <- warningmsg
    if (!is.na(warningmsg1))
      CapHist[nrow(CapHist)+2,"ClumpNum"] <- warningmsg1
    if (!is.na(warningmsg2))
      CapHist[nrow(CapHist)+3,"ClumpNum"] <- warningmsg2
    write.csv(CapHist, file=resultFile, row.names = FALSE)
    warning('Capture History was successfully created', call. = F)
  }
  
  
  
}



# END FUNCTIONS


### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### ### 


#### EXECUTE FUNCTIONS ####

FixTimeStamps(csvFile, depcsvFile)

CalculateSamplePeriod(csvFile, depcsvFile)

CreateCaptureHistory(samplePeriod)

