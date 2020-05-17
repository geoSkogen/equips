
NOTE: RE: security - this plugin is currently only configured to lookup locations

$stripped query requires further validation before being injected into text content

===============
HOW YOU DO THIS
===============
Specify:
1) which URL parameter you want to query, e.g., /?location=1234567
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
Fallback 1 : you

Example:
Verminators of [eq_location] have been keeping [eq_city] vermin-free since 1936.

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
optional stylesheet enqueue for fonts awesome and custom footer.

3)
Fix broken path to global database in multisite install environment.

4)
Inject values into database table via single sql string instead of row-by-row,
escape dependency on ADD THESE LINES TO wp-config.php DURING INSTALLATION:

define( 'WP_MEMORY_LIMIT', '256M' );
set_time_limit(300);
