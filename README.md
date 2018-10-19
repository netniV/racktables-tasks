# Tasks plugin for RackTables

This is a beta version of the Tasks plugin for RackTables.  It should be
installed in the normal way plugins are installed under RackTables.  Once
enabled, it will create TasksItem and TasksDefinitions to handle the
storage and creation of tasks.

## Installation

Install this plugin in the normal method for RackTables plugins.

## Definitions

To create a definition, you can either use the top `Tasks` link, or via
`Configuration` -> `TasksDefinitions`.  Once a definition has tasks associated
to it, you can not delete it but you can disable it.

TasksDefinition's can have two modes of operation:

- Next Due

- Scheduled

When a TasksDefinition is disabled, any outstanding tasks are automatically
closed by the system and the note is replaced with a comment to that effect.

## Next Due

When using the Next Due mode, no additional program is needed only the name,
start\_time and frequency are required.  When a TasksDefinition is enabled, any
time the definition is updated or the outstanding TasksItem is completed, a new
task is created.

The new TasksItem's created\_time will be set to either the start\_time or the
previous TasksItem's created\_time incremented by the frequency.

If the completing TasksItem's definition is disabled, no new TasksItem is
created.

## Scheduled mode

To enable scheduled mode, requires the use of a background cli program which is
in the plugin folder called `schedule.php`.  When this is run, it will search
for all TasksDefinition's that are outstanding and are flagged as being
Scheduled.

To be defined as outstanding, either the processed\_time, or if not defined the
start\_time, is compared against the current date.  If a TasksDefinition is
outstanding, a new TasksItem is created before updating the TasksDefinition's
processed\_time.

If there is an error whilst creating or updating either record, both records are
rolled back to their original state.

If the completing TasksItem's definition is disabled, no new TasksItem is
created.

## Frequency

Frequency is a very flexible and powerful field.  By default, when a browser
supports it, there will be a list of predefined entries that can be selected to
be used as the frequency.

The field must be entered in one of the following styles:

- _**format**_

  The format can be multiple formats separated by semi-colons.

  Each format may take one of the following forms:

  - *relative format*

     A relative format is the PHP term for modifying date/times and for more
     information on them, please see [PHP's relative formats](http://php.net/manual/en/datetime.formats.relative.php)

  - *interval format*

     An interval format is the PHP term for specifying date/time intervals for more
     information on them, please see [PHP's interval\_spec](http://php.net/manual/en/dateinterval.construct.php)

- _**label (format)**_

  The label is purely a descriptive term that is shown to help the user
  understand what the format is attempting to achieve. The format must match the above format style


## Examples

In any given frequency, multiple formats can be used as long as they are separated by semi-colon's (;).  Eg, ***next monday; next tuesday*** would move forward up to the next monday, then the following tuesday.  This gives the powr and flexibility to be able to customise the frequency to what ever you would like.

### Simple relative formats

If you try to add time to any relative format, it must come at the end or be a separate format after a semi-colon (;)

Frequency | Meaning
:--- | :---
next monday | This has no label, just the frequency which moves to the next Monday
next tuesday | moves to next Tuesday
next wednesday | moves to next Wednesday
next thursday | moves to next Thursday
next friday | moves to next Friday
next saturday | moves to next Saturday
next sunday | moves to next Sunday
first tuesday of next month | Moves forward a month, and finds the first Tuesday
last tuesday of next month | Moves forward a month, and finds the last Tuesday

### Basic relative formats

**Note:** *With any of the `monthly` examples below, you may end up with an unexpected date if the original day of month is greater than 28 in the start date.  For example, if the start date is 2018-01-31, +1 month would result in 2018-03-03.  Unfortuntely, this is a PHP limiation/bug not a coding error with the plugin.*

Frequency | Meaning
:--- | :---
daily (tomorrow) | Move forward to tomorrow (auto resets time to midnight)
daily noon (tomorrow&nbsp;12:00) | Move forward to tomorrow, set time to 12pm
weekly (+1&nbsp;week&nbsp;midnight) | Add one week, reset time to midnight
4 weekly (+4&nbsp;week&nbsp;midnight) | Add four weeks, reset time to midnight
monthly (+1&nbsp;month&nbsp;midnight) | Add one month, rest date to midnight.
quarterly (first&nbsp;day&nbsp;of&nbsp;this&nbsp;month, +3&nbsp;months&nbsp;midnight) | Move to the first of the current month, add 3 months, reset time to midnight
semi-annual (+6&nbsp;months&nbsp;midnight) | Add 6 months, reset time to midnight.
annual (+1&nbsp;year&nbsp;midnight) | Add a year to the date, reset to midnight
first of month (first&nbsp;day&nbsp;of&nbsp;next&nbsp;month) | Move to the first of the current month, add a month
last of month (last&nbsp;day&nbsp;of&nbsp;next&nbsp;month) | Move to the last day of the next month.  Note, that for leap years, this will move to the 29th of Feburary which may not be expected.

### Advanced relative formats

**Note:** *With any of the `monthly` examples above, you may end up with an unexpected date if the original day of month is greater than 28 in the start date.  Below, this is circumvented by using* **first day of this month** *which allows the system to move forward correct in months*

Frequency | Meaning
:--- | :---
monthly 15th (first&nbsp;day&nbsp;of&nbsp;this&nbsp;month; next&nbsp;month; +15&nbsp;days) | This performs two frequencies together.  Move to the first of the current month; Next add a month;  Next add 15 days. It is important to use the semi-colon (;) in this example because if you use first/last day of, and +X days in the same frequency, only the first will be used.
monthly first (first&nbsp;day&nbsp;of&nbsp;this&nbsp;month, next&nbsp;month) | Move to the first of the current month, add a month.  This circumvents the issue above where the month is incremented incorrectly for days greater than 28th of the month in the `monthly` example.
semi-annual first (first&nbsp;day&nbsp;of&nbsp;this&nbsp;month, +6&nbsp;months&nbsp;midnight) | Move to the first of the current month, add 6 months, reset time to midnight

### Interval format

The format for any interval format starts with a capital letter P, for "period." Each duration period is represented by an integer value followed by a period designator. If the duration contains time elements, that portion of the specification is preceded by the letter T.

Designator | Description
---: | :---
Y | years
M | months
D | days
W | weeks. These get converted into days, so can not be combined with D.
H | hours
M | minutes
S | seconds

### Interval examples

It should be noted that, as with relative formats, adding months can result in n unexpected shift in days when added to a date where the day is beyond the 28th.

Format | Description
---: | :---
P2Y4DT6H8M | add 2 Years, 4 Days, 6 Hours and 8 Minutes
P3M | add 3 months
PT3M | add 3 minutes

It is recommended to use the relative format over the interval format to circumvent problem situations.

