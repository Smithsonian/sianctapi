################################################################
#Species Rarefaction Curves 
################################################################

require(vegan)
require(reshape)
require(xtable)
require(plyr)
require(df2json)
require(ggplot2)
require(lubridate)

args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

#this setwd command will of course change or be eliminated depending how this is set up in the server
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014")

####################
#Importing the data
###################
#data <- read.csv("SampleOutput_Final.csv")
#data <- read.csv("si2.csv")
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014/Sample Output")
#data <- read.csv("Diversity2.csv")
data <- read.csv(csvFile)

#Remove all NA rows from the data set in the count column which eliminates the spaces
data <- data[complete.cases(data$Count),]

#removing all humans, and other inappropriate detections
data.an <- subset(data, !Common.Name %in% c("Camera Trapper","Calibration Photos","No Animal","Time Lapse","Human, non staff","Bicycle","Camera Misfire","Vehicle","Animal Not On List"))

#subset the data to remove all Unknown and Domestic species
data.t1 <- subset(data.an,!grepl("Unknown*",data.an$Common.Name))
data.t <- subset(data.t1,!grepl("^Domestic",data.t1$Common.Name))
#is.na(data.t$Common.Name)

####################
#Reshaping data to format for species richness package
####################
#preparing dataset for reshaping, by indentifying all variables as ID values that can be used to 
#create rows or columns and count as a numeric value

data.m <- melt(data.t,measure="Count")

#reshape the data into the form needed for the species richness package (rich function)
#every deployment is cast as a separate row (sample) and each species is cast as a separate column
#the value in each cell is the sum of the count value, giving us the number of individuals

data.rc<- cast(data.m, Deploy.ID ~ Common.Name, sum)

#re-assign the 1st column as row names so they are not used in the function 
rownames(data.rc) <- data.rc[,1]
data.rc[,1] <- NULL

#calculates the number of species for number of cameras with 500 bootstrap repetitions 
spp.rc <- specaccum(data.rc,"rarefaction",1000)

#create a list of number of species for increasing number of samples
species <- data.frame(predict(spp.rc))

#calculates the number of species that equals 95% of observed species
species.max <- 0.95*max(species)
samples <- subset(species,species<=species.max)
#class(samples)
cameras <- nrow(samples)

###################
#Plot species richness with the number of cameras and number of camera nights needed to sample 95% of species
###################
jpeg(resultFile,width=750,height=530,units="px",pointsize=14,quality=100)
plot(spp.rc,ci=2,ci.type="line",ci.col=8,ci.lty=3,xlab=c("Number of Cameras", "(95% Confidence Interval Shown in Gray)"),ylab="Number of Species",
     main=c("Species Accumulation Curve",paste(cameras,"Cameras Needed to Capture 95% of Species","(",paste(species.max,"spp.)")),"Values Assuming 21 Day Average Deployment"))
abline(h=species.max,v=cameras)
dev.off()

