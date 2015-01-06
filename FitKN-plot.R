
require(circular)

setClass("circmod", representation(model="list", pdf="numeric", x="numeric"))
setClass("kernvm", representation(data="list", bw="numeric", adj="numeric"))

args <- commandArgs(TRUE)
csvFile <- args[1]
resultFile <- args[2]

####################
#Kernel functions
####################

#Bandwidth calculation
bw.calc <- function(dat,K=3)
{	minfunc <- function(kap,k,dat)
	{	trigmom <- trigonometric.moment(circular(dat),k,center=T)$rho
		(besselI(kap,k)/besselI(kap,0) - trigmom)^2
	}
	kapk.calc <- function(k,dat)
		optimise(minfunc,c(0,100),k,dat)$minimum
	kap <- max(sapply(1:K, kapk.calc, dat))
	((3*length(dat)*kap^2*besselI(2*kap,2)) / (4*pi^0.5*besselI(kap,0)^2))^(2/5)
}
#von mises kernel pdf for points x given data dat and optionally band width bw - if not provided, calculated from the data
dvmkern <- function(x,dat,adj=1,bw=NULL)
{	if(is.null(bw)) bw <- bw.calc(dat)
	density.circular(circular(dat),circular(x),adj*bw)$y
}
FitKN <- function(dat,adj=1)
{	bw <- bw.calc(dat)
	mod <- new("kernvm", data=list(dat), bw=bw, adj=adj)
	x <- seq(0,2*pi,pi/256)
	pdf <- dvmkern(x,dat,adj,bw)
	new("circmod", pdf=pdf, x=x, model=list(mod))
}

####################
#Flexible plot method
####################

setMethod("plot", "circmod",
	function(x, plot.hrs=T, plot.frq=T, plot.dat="h", title=NULL, add=F, ...)
	{	if(add) plot.dat==F
		mod <- x
		x <- mod@x
		y <- mod@pdf
		dat <- mod@model[[1]]@data[[1]]
		if(plot.hrs)
		{	x <- x*12/pi
			dat <- dat*12/pi
			maxbrk <- 24
			xaxticks <- c(0,24,4)
		}else
		{	maxbrk <- 2*pi
			xaxticks <- NULL
		}
		if(plot.frq) 
		{	y <- y*length(dat)*pi/12 
			barmax <- max(hist(dat, breaks=seq(0,maxbrk,maxbrk/24), plot=F)$counts)
		}else
		{	barmax <- max(hist(dat, breaks=seq(0,maxbrk,maxbrk/24), plot=F)$density)
			if(plot.hrs) y <- y*pi/12
		}
		if(add) lines(x, y, ...) else
			plot(x, y, type="l", main=title, xaxp=xaxticks,  las=1,
				ylim=c(0,max(max(y),barmax)), ylab="", xlab="Time", ...) 
		if(plot.dat=="h") hist(dat, breaks=seq(0,maxbrk,maxbrk/24), freq=plot.frq, add=T) else
		if(plot.dat=="r")
			for(i in 1:length(dat)) lines(rep(dat[i],2),max(y)*-c(0.05,0.02), lwd=0.1)
	}
)

### the original script lines, where the TIME column in data has values between 0 and 1
# data <- read.csv("csv_time_of_detection-1.csv")
# i <- data$SPECIES_NAME_COMMON=="coati"
# mod <- FitKN(data$TIME[i]*2*pi)
### the modified script lines, where the time is taken from the Begin.Time column with values like 2004-04-23 13:48:00.000
#data <- read.csv(file=observation_table,head=TRUE,sep=",")
data <- read.csv(csvFile)
i <- data$Species.Name[1] # selects the rows for the species
#dayTime <- data$Begin.Time[i] # selects the time column
dayTime <- data$Begin.Time # selects the time column
if (length(dayTime)<1) {
	stop("No observations for the selected species name in the selected observation tables")
}else {
	hours <- substr(dayTime, 12 ,13) # selects the hour part of the time
	timeOfDay <- (as.numeric(hours)+1)/24 # scales to values between 0 and 1
	mod <- FitKN(timeOfDay*2*pi)
	png(file=resultFile)
	plot(mod)
	line <- paste(length(dayTime)," observations of ", i)
#	title( c(line, object_pids) )
	title( c(line) )
	dev.off()
}
