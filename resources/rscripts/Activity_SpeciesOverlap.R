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
#This is assuming that the API returns a list with a TWO species only
###################
#data <- read.csv("SampleOutput_Final_TwoSpp.csv")
#data <- read.csv("Bicycle_Coyote_API_7_21_14.csv")
#data <- read.csv("Bear_Coyote_APIJuly14.csv")
data <- read.csv(csvFile)
#summary(data$Common.Name)

##########
#Coerce the date and time values to a date and time format in R
#########
#data$End.Time <- mdy_hm(data$End.Time)
data$End.Time<-gsub("T"," ", data$End.Time,fixed=TRUE)
data$End.Time <- ymd_hms(data$End.Time)
#check that coversion was correct
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
range(na.omit(data$endtime_rad))
2*pi

#########
#subset data to two species
#########
spp <- split(data,data$Common.Name)

sppA.temp <- data.frame(spp[1])
sppB.temp <- data.frame(spp[2])

#remove NA values from the radian columns
sppA.temp <- sppA.temp[complete.cases(sppA.temp[,19]),]
sppB.temp <- sppB.temp[complete.cases(sppB.temp[,19]),]

grpA.gph <- sppA.temp[,19]
grpB.gph <- sppB.temp[,19]

############
#Overlap plot as png
###########
jpeg(resultFile,width=750,height=530,units="px",pointsize=14,quality=100)


# load logo
ima <- readPNG(file.path(workflowPath, "images/emammal_nobackground_logo_bw.png"))
#ima <- readPNG("emammal_nobackground_logo_bw.png")

#create function to place the logo
logoing_func<-function(logo, x, y, size){
  dims<-dim(logo)[1:2] #number of x-y pixels for the logo (aspect ratio)
  AR<-dims[1]/dims[2]
  par(usr=c(0, 1, 0, 1))
  rasterImage(logo, x-(size/2), y-(AR*size/2), x+(size/2), y+(AR*size/2), interpolate=TRUE)
}


overlapPlot(grpA.gph, grpB.gph, xlab="Time of Day", ylab="",yaxt="n", 
            main=c(paste(sppA.temp[1,10],"vs.",sppB.temp[1,10]),paste("Observations =",length(grpA.gph), "and", length(grpB.gph))))
mtext("Activity Level",side=2,line=0.8)
legend("topleft", legend=c(as.character(sppA.temp[1,10]),as.character(sppB.temp[1,10]),"Activity Overlap"), lty=c(1, 2,0), col=c("black", "blue","lightgrey"), pch=c(NA,NA,15), pt.cex=2.5,bty="n")

#add the logo
logoing_func(ima, x=0.50, y=0.90, size=0.15)

#add the plot again
dev.off()
