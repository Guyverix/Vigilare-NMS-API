Graphite:
  This was the first graphing system that was implemented.  It is quite stable, however I believe that the search
  and parsing ability is going to have to be enhanced in the future.  It is kind of painful to search Graphite
  for graphs specific to the host, and get them into a nice list.  The search is non-trivial and clunky.

Database:
  This overall has ended up being one of the easiest ways to store data.  Duh!  Right now, it is still doing direct
  database inserts.  This is going to get changed to using the API.  No random adhoc functions should be talking to the
  database.  API all the way if I am doing this right.  As long as it is remembered that it is storing JSON data into
  the performance database for single value fast retrieval, I think this will continue to work well-ish.  Long term
  this may not be the best solution since we are going to be able to store things that are not performance or status
  related.  
  An alternative would be an in-memory database that populates the longer the system runs.  All transient
  data that can be easiy gotten after a restart.  This will have to be investigated to keep disk IO down on trivial metric
  data.  It should also make UI a little better performance wise to get things quickly.  Redis or memached will have to
  be looked into.  Probably Redis, as I am investigating that for user auth validation.

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


Plan: as of 05-29-23(meh)
I currently envison a default of the $!@#$! RRDtool for saving metrics, as it can more easily do future
projections that Graphite simply is not designed to do.  Also the confidence banding on display or even
events from it I think would be a big win when looking at data stored.

Eventing off RRD going forward will likely end up being a pull of last value from the RRD file via
the rrdtool info and parse the DS and event if outside of what we are expecting.  Additionally this
should also allow for decent thresholds to be defined outside of the RRD database itself.  This will
tie into the template system for getting the metric name we care about and doing a fast CHEAP check
of the values inside the RRD file.

Variable names for all of these sendMetricTo### scripts are going to have to be cleaned up.  There were
some assumptions made when I wrote them that make these values confusing for extending the functionality
or behavior of the scripts.  Getting something standardized would be a big win when I have to change things
six months from now for new features, etc.


Todo:
  Some cleanup to do: parseRaw()  Get this more uniform! (WIP)
  Vars should become generic names, as not everything we can save is an oid.  This is all over the place right now.
  So far RRD is configured this way with a cleaner convention.  Need to update the others as well.
  Additional debug output will also need to be done with the manual testers when creating new templates.
    * hostname
    * valuesToStore
    * checkName
    * checkAction
    * cycle

  Additional examples of what parseRaw() should expect on input for testing?
  Write a skel for RRD like the ones for Graphite.
  More testing against NRPE results.  This is likely going to be with a VERY rigid rule set.
    * NRPE scripts can be loosy-goosy in the output.  We cannot allow slop to get into Graphs or databases.
    * NRPE scripts can be home grown, and due to this the quality and results may be lacking.  Default to no
      metric data stored at all unless rules are stricly adhered to.

  Going to have to write some kind of RRD rendering system to work with the files, and clean up old images.
  This is going to have to happen sooner rather than later to build out the GUI.  Investigate what kind
  of API will have to be written to deal with the image files and how we are going to keep things clean.

  I must get better error correction into the graphing functions.  Right now there is not enough
  logging of why things fail.  It does not become a dumpster fire, but does not say how to fix the problems
  or really give any breadcrumbs on what do to for addressing the problems.  Failures here should
  bubble up to the calling functions and they should log the failure AND (maybe) event when things choke.

  Never forget that this is intended for NOC/DevOops teams.  They may not be devs.  Give them some hope in
  how to figure out problems, and duplicate them whenever possible.
