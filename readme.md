Smoke Shows
==========

This is a WP plugin which registers a custom post type for live radio shows and a custom taxonomy for genre. It also registers a meta box on the post editor screen, allowing the user to specify a transmission time and day.

**It assumes all shows are one hour in duration and start on the hour.**

API endpoints
-------------

It also adds extra endpoints to the Wordpress WP-JSON api, allowing external services to retrieve the weekly schedule and see the show playing now.

The new endpoints are:

* /WP-JSON/shows/schedule
* /WP-JSON/shows/now_playing

The /schedule endpoint returns an array of days. Inside each day, there is an array of shows transmitted on that day. Each show is an object containing an icon, genre, transmission time and date, and so on.

The /now_playing endpoint checks the current date and time and returns a show if there is one scheduled for that time. Otherwise, it will return success: 0.
