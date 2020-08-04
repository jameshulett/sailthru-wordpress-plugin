Sailthru WordPress Plugin
=========================

For full documentation on this plugin please visit http://docs.sailthru.com/integrations/wordpress

For an list of bugs fixed and features released in this update please review the [Changelog](changelog.md).

Added support for three additional attribute types to the shortcode: source, redirect and style-*.

Source determines the CASL source code. A user variable will be populated with this value, as well as the Sailthru source field if the subscriber was not found in the account via the API.

Redirect is the URL path to which to redirect after successful submission. This is stored in the transient cache during widget rendering and retrieved upon submission to avoid external influence. This function uses the JavaScript window location path name to limit redirect to the current domain.

The style-* attributes are used to alter the appearance of form elements. A CSS style section is created for each form based on the values provided in these attributes. The format is style-<selector>-<css attribute>="value". For example, style-label-color="#00FF00" would set all label colors to blue. The values are aggressively sanitized. The vast majority of CSS values are compatible but url based constructs such as "url()" are not supported.
