#Create capture history in camtrapR from eMammal .csv files
#Brent Pease (@BrentPease1) - modified by M. Cove and J. Zhao with permission and assistance from B. Pease
#Note: in camtrapR, a "station" correlates to an eMammal "deployment"

tryCatch({
#code to read in eMammal Inputs from API
args <- commandArgs(TRUE)
csvFile <- args[1]
depcsvFile <- args[2]
clump <- args[3]
resultFile <- args[4]
#csvFile<-"okaloosadeer.csv"
#depcsvFile<-"okaloosadeerdep.csv"
#clump <- 7
#resultFile<-"deer.csv"

#Code to set the time zone - Not Important to change, but required for later packages
ols.sys.timezone <- Sys.timezone()
Sys.setenv(TZ = 'GMT')

#install.packages("data.table")
#install.packages("camtrapR")
library(data.table)
library(camtrapR)
#library(here)
list.of.packages<-c("data.table",'camtrapR') 
new.packages<-list.of.packages[!(list.of.packages %in% installed.packages()[,"Package"])]
if(length(new.packages))print("warning: package not installed!")

#read in datasets
sequences <- fread(csvFile)
cameras <- fread(depcsvFile)
#remove spaces from column headers and replace with '.'
names(sequences) <- gsub(" ",".",names(sequences))  

#####Work flow####
#1. Create 'camera station table' (This is an object (or file) describing the name, location, and date/time of all camera traps)
#2. Using the camera station table, create a 'camera operation matrix' (this is an object (or file) describing the data/time a camera was operating and the total number of days camera was running)
#3. Format initial dataset object (in this case, sequences) to be a 'record table' (This is an object (or file) containing all unique capture events, their location, and date/time)
#4. Create capture history (i.e., detection history) of specified species using camtrapR::detectionHistory
##################

#1.TO create camera station table, first find start and end camera dates 

#replace 'T' in x.Time with " " (a space)
sequences$Begin.Time <- gsub("T"," ",sequences$Begin.Time)
sequences$End.Time <- gsub("T"," ",sequences$End.Time)

# format with as.POSIXct()
sequences$Begin.Time <- as.POSIXct(sequences$Begin.Time, format ="%Y-%m-%d %H:%M:%S")
sequences$End.Time <- as.POSIXct(sequences$End.Time, format ="%Y-%m-%d %H:%M:%S")
cameras$actual_date_out <- as.POSIXct(cameras$actual_date_out, format = "%Y-%m-%d") 
cameras$retrieval_date <- as.POSIXct(cameras$retrieval_date, format = "%Y-%m-%d")
#str(cameras$actual_date_out)
#str(cameras$retrieval_date)

#subset cameras to have only two columns begin and end date, and rename 
cameras_dates <- cameras[,c("deployment_id","actual_date_out","retrieval_date")] 
colnames(cameras_dates) <- c("Deployment.ID", "Start.Date","End.Date")
#start.dates <- cameras_dates[,min(Begin.Time, na.rm=T),by=Deployment.ID] #find start date for each camera
#colnames(start.dates) <- c("Deployment.ID", "Start.Date")
#end.dates <- cameras_dates[,max(Begin.Time, na.rm=T),by=Deployment.ID] #find end date for each camera
#colnames(end.dates) <- c("Deployment.ID", "End.Date")
# remove duplicates (sometimes there is 2 or more events with same first or last Begin.Time)  
#start.dates <- start.dates[!duplicated(start.dates$Deployment.ID),]
#end.dates <- end.dates[!duplicated(end.dates$Deployment.ID),]

### merge with sequences
#sequences <- merge(sequences, start.dates, by = "Deployment.ID", all.x = T, suffixes = '')
#sequences <- merge(sequences, end.dates, by = "Deployment.ID", all.x = T, suffixes = '')
sequences <- merge(sequences, cameras_dates, by = "Deployment.ID", all.x = T, suffixes = '')


#just need the following rows for camtrapR::camera trap station information (CT station info):
#Station (deploy.id), location (lat/lon), setup.date, retrieval.date, problem_from1, problem_to1
#problem_from and problem_to are columns to let camtrapR know of a camera malfunction

seq_short <- sequences[, .(Deployment.ID,Actual.Lon,Actual.Lat,Start.Date,End.Date)]
names(seq_short) <- c("Station","Longitude","Latitude","Setup_date","Retrieval_date") #clean up the names

#add empty columns for problematic cameras
seq_short <- seq_short[,`:=`(Problem1_from="",Problem1_to="")] 

#make setup_date and retrieval_date column only contain date and not time
#seq_short$Setup_date <- substr(seq_short$Setup_date,1,10) 
#seq_short$Retrieval_date <- substr(seq_short$Retrieval_date,1,10) 

deployments <- seq_short[!duplicated(seq_short$Station)] #return only one row of each Station
print(deployments)  #make sure this looks good
deployments <- as.data.frame(deployments) #camtrapR needs data.frame. data.tables are data.frames, but...


# 2. create camera operation matrix
# for an example of a CTtable, load data(camtraps) from camtrapR package then View(camtraps)

camop <- cameraOperation(CTtable      = deployments,                #this is our CT station information  
                         stationCol   = "Station",        #which column contains station information 
                         setupCol     = "Setup_date",     #which column contains setup_date info
                         retrievalCol = "Retrieval_date", #Ditto 
                         hasProblems  = FALSE,            #were there camera malfunctions?
                         dateFormat   = "%Y-%m-%d"
)
cam.tmp.min <- apply(camop, MARGIN = 1, function(X){min(which(!is.na(X)))})    # 1st day of each station
cam.tmp.max <- apply(camop, MARGIN = 1, function(X){max(which(!is.na(X)))})    # last day of each station

rec.tmp.min  <- aggregate(as.Date(sequences$Begin.Time, tz = ols.sys.timezone),
                          list(sequences[,Deployment.ID]),
                          FUN = min)
rec.tmp.max  <- aggregate(as.Date(sequences$End.Time, tz = ols.sys.timezone),
                          list(sequences[,Deployment.ID]),
                          FUN = max)
#3. We just now need to format our initial .csv (in this case, 'sequences') 
#to be a 'record table'
#at minimum, we need a column for StationID, SpeciesID, and date/time. 
#for an example, load data(recordTableSample) from camtrapR package then View(recordTableSample)

seq_rectable <- sequences[,.(Deployment.ID,Common.Name,Begin.Time)]
#colnames(seq_rectable) <- c("Station","Species","DateTimeOriginal") #define column names
seq_rectable <- as.data.frame(seq_rectable) #make sure this looks good 

# species to create detection history for
species_name <- unique(sequences$Common.Name)
#species <- c('White-tailed Deer', 'American Black Bear', 'Camera Trapper') #This is list of species that we want to create the detection histories for

#4. compute detection history for a species 
# 
# range(seq_rectable$DateTimeOriginal[seq_rectable$Station == "d17702"])       # the range of DateTimeOriginal at station 01C1
# seq_short$Setup_date[seq_short$Station == "4288"]      # setup date at station 01C1
# seq_short$Retrieval_date[seq_short$Station == "4288"]   # retreival date at station 01C1
date_ranges <- data.frame(Deployment.ID = rec.tmp.max$Group.1,
                          rec.min = rec.tmp.min[match(rownames(camop), rec.tmp.min[,1]), 2],       # first record
                          rec.max = rec.tmp.max[match(rownames(camop), rec.tmp.max[,1]), 2],       # last record
                          cam.min = as.POSIXct(colnames(camop)[cam.tmp.min], tz = ols.sys.timezone),   # station setup date
                          cam.max = as.POSIXct(colnames(camop)[cam.tmp.max], tz = ols.sys.timezone)    # station retrieval date
)

if(any(date_ranges$rec.min < as.Date(date_ranges$cam.min), tz = ols.sys.timezone, na.rm = TRUE)) 
  stop(paste("deployments with sequence timestamp outside deployment date range: ",                              
             paste(date_ranges$Deployment.ID[which(
               date_ranges$rec.min < as.Date(date_ranges$cam.min, tz = ols.sys.timezone))], 
               collapse = ", " )), call. = FALSE)

if(any(date_ranges$rec.max > as.Date(date_ranges$cam.max, tz = ols.sys.timezone), na.rm = TRUE)) 
  stop(paste("deployments with sequence timestamp outside deployment date range: ",
             paste(date_ranges$Deployment.ID[which(
               date_ranges$rec.max > as.Date(date_ranges$cam.max, tz = ols.sys.timezone))], 
               collapse = ", " )), call. = FALSE)


DetHist <- detectionHistory(recordTable         = seq_rectable,                     #a list of all capture events with their location and date/time stamp
                            camOp                = camop,                #our camera trap operation matrix
                            stationCol           = "Deployment.ID",            
                            speciesCol           = "Common.Name",
                            recordDateTimeCol    = "Begin.Time",
                            recordDateTimeFormat = "%Y-%m-%d %H:%M:%S",
                            species              = species_name, #which species to create detection history for, from the species in 'seq_rectable' 
                            occasionLength       = clump,                   #This is the clumping number (e.g., number of days to aggregate in a single survey occasion)
                            day1                 = "station",           #WHen should occassions begin: station setup date("station"), first day of survey("survey"), or a specified date (e.g., "2015-12-25")?
                            datesAsOccasionNames = FALSE,               #only applies if day1="survey"
                            includeEffort        = FALSE,               #compute trapping effort(number of active camera trap days per station and occasion)?
                            timeZone             = "GMT",
                            writecsv = TRUE,
                            outDir = getwd())         #here specifies the base directory and then "Capture_Histories" specifies folder within base directory
            
#Jen to change - add data frames together

DetHist<-as.data.frame(DetHist)

cameras_dates$Common.Name<-species_name
cameras_dates$ClumpNum<-clump
DetHist<-cbind(rownames(DetHist),DetHist)
colnames(DetHist)[1]<-"Deployment.ID"

finalCSV<-merge(cameras_dates,DetHist, by="Deployment.ID")
write.csv(finalCSV, file  = resultFile, row.names=FALSE)

}, error=function(e) {
  print(paste(e,"Please contact eMammal@si.edu with these deployment IDs for help fixing this.")) #jen to clean up
}, warning=function(w){
  print(paste(w,"Please contact eMammal@si.edu for help fixing this."))
}, finally={}
)



#write.csv(as.data.frame(DetHist), file  = 'all.csv')
#change name of csv to species name + clump
