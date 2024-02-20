There are currently several data storage types.  Below are the ones implemented currently...

Graphite:
  This was the first graphing system that was implemented.  It is quite stable, however being older I suspect it is
  not as widely used any longer in the outside world.  This is the lightest weight graph option currently in use.
  This is all server side, and the GUI only has to display an image.  Minimal impact on the API itself.

Database:
  This overall has ended up being one of the easiest ways to store data.  Duh!  Although this is simple enough to do
  I dont see overall this being very practical.  People will just throw crap in here by default since it is simple
  and we will not be able to readily graph data since inserts here are always overwrite.  This is generally used for
  simple string data and output like that where it is not generally a "metric".

DatabaseMetric:
  Save the metric data as an array for display in the GUI.  This currently has some hard coded support but
  nothing really defined for adhoc arrays of random sizes being returned on query.  This is likely going
  to need some love in the near future.

Rrd:
  One would think this would be the easiest way to store metric data for graphs.  However working with RRD is finicky.
  There are both gotchas, and unexpected limits in dealing with RRD.  First the DS name must be 19 chars or less.
  We also have to make very certain on how we build out the database as if it is incorrect we gotta nuke it and loose
  potentially all other data metrics inside it due to a single change.  The fact that the file never changes size after
  creation is a nice plus.  But storing a lot of metrics is really going to increase disk IO.  Either the cache system
  is going to have to be implemented, or watch the system IO closely and see if we need to federate saving the RRD files
  off host and use NFS for graph creation is a possibility with really large installations.

Influxdb:
  I have not started on this one yet.  While it is likely the most flexable and useful for really serious metrics, it
  may not be the best bulk solution.  Seeing as the function of an NMS is Fault Managment, not specifically Perf Managment
  I hesatate to write this into the initial release.  This may end up being more of a bolt on later to leverage
  existing Influx installations from other tools.

Debugger:
  This simply dumps a debug file under files/HOSTNAME/debugger in json format.  There is a script
  in this directory which will read it out as print_r output to make things easier to understand.

None:
  Just what it says on the side of the box.  Dont store metrics.  Useful for NRPE service checks where
  we only care about the exit code of the NRPE check itself.

Plan: as of 05-29-23(meh)
I currently envison a default of the RRDtool for saving metrics, as it can more easily do future
projections that Graphite simply is not designed to do.  Also the confidence banding on display or even
events from it I think would be a big win when looking at data stored.

Eventing off RRD going forward will likely end up being a pull of last value from the RRD file via
the rrdtool info and parse the DS and event if outside of what we are expecting.  Additionally this
should also allow for decent thresholds to be defined outside of the RRD database itself.  This will
tie into the template system for getting the metric name we care about and doing a fast CHEAP check
of the values inside the RRD file.

Variable names for all of these sendMetricTo scripts are going to have to be cleaned up.  There were
some assumptions made when I wrote them that make these values confusing for extending the functionality
or behavior of the scripts.  Getting something standardized would be a big win when I have to change things
six months from now for new features, etc.

Plan: as of 02-20-24
Right now I am simply cleaning up after the initial migration to github.  Getting rid of all references
to hard coded paths, and getting them all relative has been a little painful, but should be done today.
Next is going to make sure that all references to a specific database are removed and ONLY set from the
config file.  Right now there are a couple of really early files that have values hard set as config.php
did not exist when I wrote them.

Todo:
  Additional examples of what parseRaw() should expect on input for testing?
  Write a skel for RRD like the ones for Graphite.
  More testing against NRPE results.  This is likely going to be with a VERY rigid rule set.
    * NRPE scripts can be loosy-goosy in the output.  We cannot allow slop to get into Graphs or databases.
    * NRPE scripts can be home grown, and due to this the quality and results may be lacking.  Default to no
      metric data stored at all unless rules are stricly adhered to.
  Write a skel for check type Curl.  There is a lot of metric data returned from a curl call, and if someone
  decides to do curl monitoring, getting this information into a graph would likely be quite useful.  Currently
  there is no curl daemon, but I can see that coming soon.

  I still need to remove old commeted out code so only valid stuff is in place.  I will likely leave
  debugger code in place but commented out since that is so darn useful.

  I must get better error correction into the graphing functions.  Right now there is not enough
  logging of why things fail.  It does not become a dumpster fire, but does not say how to fix the problems
  or really give any breadcrumbs on what do to for addressing the problems.  Failures here should
  bubble up to the calling functions and they should log the failure AND (maybe) event when things choke.

  Never forget that this is intended for NOC/DevOops teams.  They may not be devs.  Give them some hope in
  how to figure out problems, and duplicate them whenever possible.
