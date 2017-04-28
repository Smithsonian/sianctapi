require(reshape)
require(plyr)
require(df2json)
require(rjson)

args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

#Import data
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014/Sample Output")
#data <- read.csv("sianctapi-exampleoutput-8282015.csv")
#data <- read.csv("SampleAPIOutput.csv")
data <- read.csv(csvFile)
#Remove all NA rows from the data set in the count column which eliminates the spaces
data <- data[complete.cases(data$Count),]
#add column of row values of 1 for each sequence
data$seq.count <- rep(1,nrow(data))
#Reshape data for count and detections summary
data.m <- melt(data,id="Common.Name",measure.vars=c("Count","seq.count"))
data.spp <- cast(data.m, Common.Name ~ variable, sum)
#rename columns
data.spp <- rename(data.spp, c("Common.Name"="Common Name", "Count"="Individuals", "seq.count"="Sequences"))
#export to json
data.spp <- as.data.frame(data.spp)
sppdashboard.json <- df2json(data.spp)
#save json outputs to json files
write(sppdashboard.json,resultFile)

