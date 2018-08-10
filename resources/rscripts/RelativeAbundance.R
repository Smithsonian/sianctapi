######################### Relative Abundance ############################

################################################################################
#### Load Necessary Packages #######
#The below packages are the ones used throughout this code template. Please install
#and load the below packages before proceeding 
#If you do not have one of these packages you can install with the following code:
list.of.packages<-c("data.table","dplyr",'xtable','reshape2',"ggplot2",'ggmap','overlap','activity','camtrapR','rgdal')
new.packages<-list.of.packages[!(list.of.packages %in% installed.packages()[,"Package"])]
if(length(new.packages))install.packages(new.packages)

library(data.table)
library(xtable)
library(rgdal)
library(dplyr)
library(plyr)
library(ggplot2)
library(reshape2)
library(ggmap)
library(overlap)
library(activity)
library(camtrapR)


library(lubridate)
library(jpeg)
library(png)


#######Loading and organizing data ##########

#Load from eMammal dataset inputs
args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

#this setwd command will of course change or be eliminated depending how this is set up in the server
#setwd("C:/Users/ZhaoJJ/Dropbox (Smithsonian)/Documents/")

#Load the latest dataset downloaded from the website
#Note that the filename will change each time so make sure it is
#edited properly below
#csvFile <- fread("okaloosa.csv")
#SiteInfo<-fread(file="SiteInfo.csv")
#resultFile<-"testing.jpeg"

#################Description of effort in a table #######


####### Make Camera Night Output Table ############
#Generate a table with the camera deployment days
csvFile$Date=substr(csvFile$Begin.Time,1,10)
csvFile$Date<-as.Date(csvFile$Date,
                   format="%Y-%m-%d")
DeploymentNightTable<-ddply(csvFile,~csvFile$Deployment.Name,summarise,TrapNights=length(unique(Date)))

DeploymentNightTable2<-xtable(DeploymentNightTable)

#Saving your camera nights table in a few ways:
#print.xtable(DeploymentNightTable2, type="html", file="Summary_Table.html")  #Saves as an HTML File
#Open file in web browser
#Open the generated HTML file in your browser (may I recommend Firefox)
#Saves as a csv file - table can be edited in excel 
#write.csv(DeploymentNightTable, file = "CameraNightTable.csv", row.names = FALSE) 
#Saves as a txt file - table can be made in word
#write.table(DeploymentNightTable, file = "CameraNightTable.txt", row.names = FALSE)


#Total and average Trap Night per Subproject
SubprojectTrapNights = ddply(csvFile,~csvFile$Subproject,summarise,TrapNights=length(unique(Date)))
#names(SubprojectTrapNights)<-c("Subproject", "Camera Nights")
#SubprojectTrapNights
AverageSubprojectTrapNight<-ddply(csvFile,~Subproject+'Deployment Name',summarise,TrapNights=length(unique(Date)))
AverageSubprojectTrapNight<-ddply(AverageSubprojectTrapNight,~Subproject,summarise,mean(TrapNights))
#AverageSubprojectTrapNight



#Total Trap Nights across the entire project
TotalTrapNights<- ddply(csvFile,~Project,summarise,TrapNights=length(unique(Date)))
#names(TotalTrapNights)<-c("Total Trap Nights", "Camera Nights")
#TotalTrapNights
AverageProjectTrapNight<-  ddply(csvFile,~Project+'Deployment Name',summarise,TrapNights=length(unique(Date)))
AverageProjectTrapNight<-ddply(AverageProjectTrapNight,~Project,summarise,mean(TrapNights))
#AverageProjectTrapNight



############ Bar graph of relative abundance  ##########
#Make data summary, detection rate for each species for the entire project
duration <- AverageProjectTrapNight[,2]
spp<-unique(csvFile$'Common Name')
dur<-rep(as.numeric(duration), length(spp))
count <- csvFile[,list(sum=sum(Count)),by='Common Name']
rate<-(count$sum/dur)*100
rate_input<-cbind(count, rate)
remove_spp<-("Camera Trapper|No Animal|Unknown Animal|Vehicle|Unknown Squirrel|Unknown Small Rodent|Unknown Rabbit_Hare|Unknown Flying Squirrel|Unknown Felid|Unknown Coati_Raccoon|Unknown Canid|Unknown Bird|Time Lapse|Reptile species|Raptor species|Owl species|Other Bird species|Northern Bobwhite|Human non-staff|Common Raven|Calibration Photos|Blue Jay|Bicycle|Animal Not on List|American Crow")
rrate<-rate_input[grep(remove_spp, rate_input$'Common Name', invert=T),]   #invert = T shows the species not designated in remove_spp. 
#If you made a list of species you are interested in use invert=F
 #Orders based on which species has the higher Detection rate



# #Make graph showing total capture counts
# rrate<-rrate[order(-rrate$sum)]
# head(rrate)
# print(Countgraph1 <- ggplot(csvFile=rrate, aes(x=reorder(rrate$'Common Name', sum), y=sum)) +
#   geom_bar(stat="identity", color="black", 
#            fill="steelblue")+
#   theme_classic() + 
#   labs(x="Species", 
#        y = "Total Count")+
#   theme(axis.text.x = element_text(angle = 90, hjust = 1, color="black"))+
#   theme(axis.text.y = element_text(color="black")))

#To save plot, run line 114
#ggsave("Countgraph1.png", width = 20, height = 20, units = "cm")


#Make graph showing detection rate
rrate<-rrate[order(-rrate$rate)]
jpeg(resultFile,width=750,height=530,units="px",pointsize=14,quality=100)
ggplot(data=rrate, aes(x=reorder(rrate$'Common Name', rate), y=rate)) +
  geom_bar(stat="identity", color="black", 
           fill="steelblue")+
  theme_classic() + 
  labs(x="Species", 
       y = "Detection Rate (count/day)")+
  theme(axis.text.x = element_text(angle = 90, hjust = 1, color="black"))+
  theme(axis.text.y = element_text(color="black"))
dev.off()
dev.off()
