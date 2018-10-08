# Tasks plugin for RackTables

This is a beta version of the Tasks plugin for RackTables.  It should be 
installed in the normal way plugins are installed under RackTables.  Once 
enabled, it will create TasksItem and TasksDefinitions to handle the 
storage and creation of tasks.

Running schedule.php will search for all TasksDefinition's that are outstanding 
and create TasksItem's for them, before updating it's processed_time.  If there 
is an error updating either record, both records are rolled back.

