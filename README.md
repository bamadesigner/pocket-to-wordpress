# Pocket to Wordpress
Working on a new WordPress plugin that will help you display your Pocket info on your WordPress site. WHen it's ready, I'll upload to the WordPress repository.

This adventure into the Pocket API was inspired because I wanted to get all of my Pocket items that were tagged "reading" and display them as a reading list on my WordPress site.

Kudos to [Rob Neu (@rob_neu)](https://twitter.com/rob_neu) for some helpful tweaks.

You'll need a Pocket consumer key and access token in order to use their API. Visit https://getpocket.com/developer/ to register an app and get a key.

You can then use https://github.com/jshawl/pocket-oauth-php to get your access token.

If you don't mind trusting some random site, you can use http://reader.fxneumann.de/plugins/oneclickpocket/auth.php to get your access token a lot quicker.

Then use the shortcode [pocket_items] or the function get_pocket_items_html() to display your pocket items.

You can also use get_pocket_items() to retrieve the item data and display as you like.
