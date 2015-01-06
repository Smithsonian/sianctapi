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
#Species Richness with Confidence Interval and Mean Values using Sample Taverna Output data
####################
#this setwd command will of course change or be eliminated depending how this is set up in the server
#setwd("X:/1 Camera Trapping/SI Data Repository/R scripts/Codes to Gert 5_15_2014")
#data <- read.csv("SampleOutput_Final.csv")
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

####################
#rich Species richness and confidence interval and other useful indices
####################

spp.rich <- rich(data.spp, verbose = FALSE, nrandom = 500)

#Computes the cumulative and average species richness over a set of samples, the associated bootstrap statistics and other useful indices.
#matrix matrix is a typical species-sample matrix. Rows correspond to samples whereas columns stand for species.
#verbose If verbose=FALSE, a simplied output is returned.
#nrandom=Number of randomizations

##################
#Export species richness results and key to headings as json
#################
#convert values to dataframe for export
spp.rich.df <- data.frame(spp.rich$cr,spp.rich$bootCR$cr.lbn,spp.rich$bootCR$cr.ubn,spp.rich$mr,spp.rich$mrsd)
spp.rich.df <- format(round(spp.rich.df,2),nsmall=2)


#rename column headings to human friendly names
colnames(spp.rich.df) <- c("Observed Species Richness", "Lower 95% Confidence Interval", "Upper 95% Confidence Interval","Mean Species Richness per Camera","Mean Species Richness Standard Deviation")
#c("spp.rich.cr"="Observed Species Richness", "spp.rich.bootCR.cr.lbn"="Lower 95% Confidence Interval","spp.rich.bootCR.cr.ubn"="Upper 95% Confidence Interval","spp.rich.mr"="Mean Species Richness","spp.rich.mrsd"="Mean Species Richness Standard Deviation"))

#Convert data frame to json for API to return
spp.rich.json <- df2json(spp.rich.df)
#print(spp.rich.json)

#save json output to file in working directory.  You can also include the file path in file name for a different place
write(spp.rich.json,resultFile)
