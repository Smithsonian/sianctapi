require(vegan)
require(rich)
require(reshape)
require(xtable)
require(plyr)
require(df2json)
require(rjson)
require(gtools)

args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

##################
#Compare the mammal diversity similarity of two places
#################
#load data
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014")
#data <- read.csv("SampleOutput_Final.csv")
#data <- read.csv("si2.csv")
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014/Sample Output")
#data <- read.csv("Diversity2.csv")
data <- read.csv(csvFile)

#Remove all NA rows from the data set in the count column which eliminates the spaces
data <- data[complete.cases(data[,14]),]

#removing all humans, and other inappropriate detections
data.an <- subset(data, !Common.Name %in% c("Camera Trapper","Calibration Photos","No Animal","Time Lapse","Human, non staff","Bicycle","Camera Misfire","Vehicle","Animal Not On List"))

#subset the data to remove all Unknown and Domestic species
data.t1 <- subset(data.an,!grepl("Unknown*",data.an$Common.Name))
data.t <- subset(data.t1,!grepl("^Domestic",data.t1$Common.Name))

#reshape the data to a community data matrix
#the critical point here is that species ID and sub project name must be ID variables
#Count column must be the measure variable
data.tmp <- melt.data.frame(data.t, measure="Count")

#reshape data to community data matrix places as rows, columns as species, and cells as sum of counts
data.cmx <- cast(data.tmp,Subproject ~ Common.Name,sum)

#re-assign the 1st column as row names so they are not used in the function 
row.names(data.cmx) <- data.cmx[,1]
data.cmx[,1] <- NULL

#calculate jaccard similarity index
jacc <- vegdist(data.cmx,"jaccard")

#create lists of park comparisons and values
tmp.list <- matrix(row.names(data.cmx))
print(tmp.list)
park.list <- combn(tmp.list,2)
park.list <- t(park.list)
print(park.list)

jacc.m <- cbind(park.list,jacc)
#rename column headings and convert to dataframe
colnames(jacc.m)<- c("Park 1", "Park 2","Jaccard Similarity")
jacc.m <- data.frame(jacc.m)

#Convert output to json for API to return
jacc.json <- toJSON(jacc.m)

#Save json to file
write(jacc.json,resultFile)
