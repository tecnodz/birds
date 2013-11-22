Birds as a CMS
==============

This is a draft document describing the CMS requirements and how it can be used to create
websites.

**Requirements**

* **Lightweight**: pages should be rendered with the lest resources as possible. Expected results: 
  under 1 MiB on less than 0.2 s for each request.

* **Multihost**: a single instance should be capable of handling multiple hosts, sites and templates.
  Content within these sites, however, might be freely distributed.

* **Rich media**: it should be able to handle videos and audio as HTTP streams. Transcoding is
  greatly expected. For images, it should be simple to resize the output.

* **File-based**: all the contents should be stored on files, for version control. Content indexing
  (the sitemap as well?) should be made by simple database files, like SQLite.

* **Innovative UI**: User interface should be dynamic and easy to use even from smartphones and limited
  devices.
