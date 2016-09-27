require(vegan)
require(rich)
require(reshape)
require(xtable)
require(plyr)
require(df2json)
require(rjson)

args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

####################
#Importing the data
###################
#this setwd command will of course change or be eliminated depending how this is set up in the server
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014")
#data <- read.csv("SampleOutput_Final.csv")
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014/Sample Output")
#data <- read.csv("Diversity.csv")
data <- read.csv(csvFile)

#Remove all NA rows from the data set in the count column which eliminates the spaces
data <- data[complete.cases(data[,14]),]

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

data.spp<- cast(data.m, Deploy.ID ~ Species.Name, sum)

#re-assign the 1st column as row names so they are not used in the function 
rownames(data.spp) <- data.spp[,1]
data.spp[,1] <- NULL

#########################
#Shannon_Weaver and Simpson Diversity Indices 
#########################
shann.spp <- diversity(data.spp,index="shannon",MARGIN=1,base=exp(1))
shann.mn <- mean(shann.spp)
shann.se <- sd(shann.spp)/sqrt(nrow(data.spp))

simp.spp <- diversity(data.spp,index="simpson",MARGIN=1,base=exp(1))
simp.mn <- mean(simp.spp)
simp.se <- sd(simp.spp)/sqrt(nrow(data.spp))

#####################
#Export results in json
#####################
#convert values to dataframe or list for creating json
spp.divstats.df <- data.frame(shann.mn, shann.se, simp.mn, simp.se)

#rename column headings to human friendly names
colnames(spp.divstats.df) <- c("Shannon-Weaver Index Mean", "Shannon-Weaver Index Standard Error","Simpson Index Mean","Simpson Index Standard Error")

#convert to JSON for API to return
divstats.json <- df2json(spp.divstats.df)

#save json outputs to json files
write(divstats.json,resultFile)
