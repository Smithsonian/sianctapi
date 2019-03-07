######################### Relative Abundance ############################

################################################################################
#### Load Necessary Packages #######
#The below packages are the ones used throughout this code template. Please install
#and load the below packages before proceeding
#If you do not have one of these packages you can install with the following code:

tryCatch({
  packages <- c('data.table',
                'xtable',
                'plyr',
                'dplyr',
                'ggplot2',
                'reshape2',
                'ggmap',
                'overlap',
                'activity',
                'lubridate',
                'jpeg',
                'png');

  available_packages <- installed.packages()[,"Package"]

  for (package in packages) {
    if (!(package %in% available_packages)) {
      stop(paste("Package ", package, " unavailable."))
    }

    capture.output(suppressMessages(library(package, character.only = TRUE, quietly = TRUE)));
  }


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
  data <- read.csv(csvFile)

  #################Description of effort in a table #######


  ####### Make Camera Night Output Table ############
  #Generate a table with the camera deployment days
  data$Date = substr(data$Begin.Time, 1, 10)
  data$Date <- as.Date(data$Date, format="%Y-%m-%d")
  DeploymentNightTable<-ddply(data,~data$Deployment.Name,summarise,TrapNights=length(unique(Date)))

  DeploymentNightTable2<-xtable(DeploymentNightTable)

  #Total and average Trap Night per Subproject
  SubprojectTrapNights = ddply(data,~data$Subproject,summarise,TrapNights=length(unique(Date)))
  #names(SubprojectTrapNights)<-c("Subproject", "Camera Nights")
  #SubprojectTrapNights
  AverageSubprojectTrapNight<-ddply(data,~Subproject+'Deployment Name',summarise,TrapNights=length(unique(Date)))
  AverageSubprojectTrapNight<-ddply(AverageSubprojectTrapNight,~Subproject,summarise,mean(TrapNights))
  #AverageSubprojectTrapNight

  #Total Trap Nights across the entire project
  TotalTrapNights<- ddply(data,~Project,summarise,TrapNights=length(unique(Date)))
  AverageProjectTrapNight<-ddply(data,~Project+'Deployment Name',summarise,TrapNights=length(unique(Date)))
  AverageProjectTrapNight<-ddply(AverageProjectTrapNight,~Project,summarise,mean(TrapNights))

  ############ Bar graph of relative abundance  ##########
  # Make data summary, detection rate for each species for the entire project
  duration <- AverageProjectTrapNight[,2]
  spp<-unique(data$Common.Name)
  dur<-rep(as.numeric(duration), length(spp))
  #count <- data[,list(sum=sum(Count)),by='Common Name']
  count<-ddply(data, .(Common.Name), summarise, sum=sum(Count))
  rate<-(count$sum/dur)*100
  rate_input<-cbind(count, rate)

  # Removing all humans, and other inappropriate detections
  data.an <- subset(rate_input, !(rate_input$Common.Name %in% c("Camera Trapper","Calibration Photos","No Animal","Time Lapse","Human, non staff","Human non-staff","Bicycle","Camera Misfire","Vehicle","Animal Not On List")))

  #subset the data to remove all Unknown and Domestic species
  data.t1 <- subset(data.an,!grepl("Unknown*",data.an$Common.Name))
  data.t <- subset(data.t1,!grepl("Domestic",data.t1$Common.Name))

  rrate<-data.t[order(-data.t$rate),]

  ylim_rough<-max(rrate$rate)+50
  ylim_max<-10*(ylim_rough%/%10+as.logical(ylim_rough%%10))

  #Make graph showing detection rate
  jpeg(resultFile, width=750, height=530, units="px", pointsize=14, quality=100)
  print(ggplot(data=rrate, aes(x=reorder(rrate$Common.Name, -rate), y=rate)) +
    ylim(0, ylim_max) +
    geom_bar(stat="identity", color="black", fill="steelblue") +
    theme_classic() +
    geom_text(aes(label=round(rate,digits=1)), vjust=-.5, size=5) +
    theme(axis.title.x = element_text(size = 20)) +
    theme(axis.title.y = element_text(size = 20)) +
    labs(x="Species", y = "Detection Rate (count/100 trap nights)")+
    theme(axis.text.x = element_text(angle = 90, hjust = 1, color="black", size=12))+
    theme(axis.text.y = element_text(color="black", size = 12)));
  dev.off();
}, error=function(e) {
  print(paste(e,"\n Please contact eMammal@si.edu for help fixing this.")) #jen to clean up
}, warning=function(w){
  print(paste(w,"\n Please contact eMammal@si.edu for help fixing this."))
})
