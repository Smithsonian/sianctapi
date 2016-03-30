args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

#Import data
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014/Sample Output")
#data <- read.csv("sianctapi-exampleoutput-8282015.csv")
#data <- read.csv("SampleAPIOutput.csv")
data <- read.csv(csvFile)
#Remove all NA rows from the data set in the count column which eliminates the spaces
data <- data[complete.cases(data[,14]),]
#remove non-wildlife detections
data.an <- subset(data, !Common.Name %in% c("Camera Trapper","Calibration Photos","No Animal","Time Lapse","Human, non staff","Bicycle","Camera Misfire","Vehicle","Animal Not On List","Unknown Animal"))
data.an$Common.Name <- factor(data.an$Common.Name)
#create table for bar graph
species <- table(data.an$Common.Name)
species <- sort(species)
#Create bar graph of detections by species
jpeg(resultFile,width=750,height=530,units="px",pointsize=14,quality=100)
par(las=2)
par(mar=c(12,6,4,2))
barplot(species, main=c(paste("Detections by Species"), paste("Total Observations =",length(data.an$Common.Name))),xlab="",ylab="Number of Detections")
dev.off()

