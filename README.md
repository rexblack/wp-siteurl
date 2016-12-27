# wp-siteurl

Dynamically set Wordpress Site-URL based on current Base-URL

This plugin solves a common problem when migrating a Wordpress-Site to another domain.
Wordpress stores the site-url in the database and will redirect immediately to this url which makes your installation unusable.
wp-siteurl will prevent this by overriding the site-url with the base-url.
