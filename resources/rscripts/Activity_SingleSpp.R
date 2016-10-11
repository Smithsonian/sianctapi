require(overlap)
require(lubridate)
require(jpeg)
require(png)

args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]
# Set workflow path for SIANCT API
workflowPath <- file.path(getwd(), "resources/rscripts");

#this setwd command will of course change or be eliminated depending how this is set up in the server
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014")
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014/Sample Output")

####################
#Importing the data
#This is assuming that the API returns a list with a single species
#IF THE API RETURNS SOMETHING DIFFERENT THIS SCRIPT HAS TO BE CHANGED
###################
#data <- read.csv("SampleOutput_Final_SingleSpp.csv")
#data <- read.csv("Coyote_APIJuly14.csv")
data <- read.csv(csvFile)

##########
#Coerce the date and time values to a date and time format in R
#########
#data$End.Time <- mdy_hm(data$End.Time)
data$End.Time <- ymd_hms(data$End.Time)
# check that coversion was correct
#class(data$End.Time)

#########
#Separate the time value from the date and create new column in the data frame with time only
#it also converts the time from milliseconds since midnight on 1970 (R classification) to time as 0-1 
#where 0 is midnight, 0.5 is noon, etc.
#########
data$end_time_num <- (hour(data$End.Time)+minute(data$End.Time)/60)/24


######## 
#Convert times to radians
### NB: time in the input must be in decimal form, range from 0 to 1, 0.5 is noon. (standart format in Excel).
########
timeRad.temp <- (data$end_time_num) * 2*pi
data$endtime_rad <- as.numeric(timeRad.temp)

### Verify times are between 0 and 2*pi
#range(na.omit(data$endtime_rad))
#2*pi

###################
#Single species activity pattern
###################
temp.gph <- data$endtime_rad
#remove NA values
temp.gph <- temp.gph[complete.cases(temp.gph)]

#single species activity plot as png
jpeg(resultFile,width=750,height=530,units="px",pointsize=14,quality=100)




# load logo
ima <- readPNG(file.path(workflowPath, "images/emammal_nobackground_logo_bw.png"))

#create function to place the logo
logoing_func<-function(logo, x, y, size){
  dims<-dim(logo)[1:2] #number of x-y pixels for the logo (aspect ratio)
  AR<-dims[1]/dims[2]
  par(usr=c(0, 1, 0, 1))
  rasterImage(logo, x-(size/2), y-(AR*size/2), x+(size/2), y+(AR*size/2), interpolate=TRUE)
}


#plot
densityPlot(temp.gph,rug=TRUE, xlab="", ylab = "",  extend = NULL,
            main=c(paste(data$Common.Name[[1]], "Activity"), paste("Observations =",length(temp.gph))))
rect(0,0,6,1, col = "grey", border = "grey")
rect(18,0,24,1, col = "grey", border = "grey")
par(new = T)
densityPlot(temp.gph,rug=TRUE, xlab="", ylab = "",  extend = NULL,
            main=c(paste(data$Common.Name[[1]], "Activity"), paste("Observations =",length(temp.gph))))
mtext("Activity Level",side=2,line=2.5)
mtext("Time of Day",side=1,line=2.2)
mtext("(Hashmarks are Animal Detections)",side=1,line=3.2)

#add the logo
logoing_func(ima, x=0.50, y=0.90, size=0.15)


#add the plot again

dev.off()
