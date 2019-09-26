ADD THIS LINE TO wp-config.php DURING INSTALLATION:

set_time_limit(300);

//NOTE: RE: security - this plugin is currently only configured to lookup locations
//see lines 132 - 145 of equips.php
//$stripped_query requires further validation before being injected into text content

===============
HOW YOU DO THIS
===============
Specify:
1) which URL parameter you want to query, e.g., location
2) which shortcode you want to use to inject that parameter's value into the
   text, e.g. eq_location (in which case you'd use [eq_location] on the page).
3) which value the shortcode should render if the specified url parameter is empty
   or not found, e.g., your area (leaving this field blank will return nothing,
   i.e., an empty string if the parameter is empty or not found).
  Example--
Param 1 : location,
Shortcode 1 : eq_location,
Fallback 1 : your area

Double-Duty:
--you can add multiple shortcodes for a single URL parameter query, for example,
if you want two different fallback values for location, depending on where and
how you're using them in the text.
Example--
Param 2 : location,
Shortcode 2 : eq_city,
Fallback 1 : left (not set) --or-- left blank

To Edit:
--Simply change the form fields and re-submit the form
--Submitting an empty field will un-set that value in the database
--Submitting an empty form will turn off all shortcodes

===========================
Design improvements needed:
===========================
1)
Add validation logic for each type of url parameter!
--currently 'location' must be numeric and will return its fallback value if
  lookup-by-number fails.

2)
Add shortcode options page for custom geoblock settings!

3)
Upgrade global namespace use to OOP protocol.IN PROGRESS!

4)
--need a solution to redundant incrementing shortcode handler functions with hard-coded
data. The shortcode handler function is called by a string argument to
add_shortcode('shortcode', 'handler'), but it does not accept an argument itself;
an ordinary workaround is to write an anonymous function into the second argument to
add_shortcode('shortcode' function () { .. etc ...}) assign a value to a variable outside the
handler's scope and allow it to inherit the value -- but because the handler is
a callback, that would be an even worse design, because there would be no guarantee
that the value assigned when the caller function was called would persist until the
callback function executed. If only a variable and an anonymous function are
being injected into the arguments to add_shortcode(), then the result is a handler
that doesn't know its own shortcode, because it's being invoked outside of the
procedure that created it.

5)
Flexible form with as many entry fields as the user requires - depends on resolution
of issues 1 & 2.
