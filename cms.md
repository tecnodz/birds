Birds as a CMS
==============

This is a draft document describing the CMS requirements and how it can be used to create
websites.


## Requirements ##

*   **Lightweight**: pages should be rendered with the lest resources as possible. Expected 
    results: under 1 MiB on less than 0.2 s for each request.

*   **Multihost**: a single instance should be capable of handling multiple hosts, sites and 
    templates. Content within these sites, however, might be freely distributed.

*   **Rich media**: it should be able to handle videos and audio as HTTP streams. Transcoding 
    is greatly expected. For images, it should be simple to resize the output.

*   **File-based**: all the contents should be stored on files, for version control. Content 
    indexing (the sitemap as well?) should be made by simple database files, like SQLite.

*   **Innovative UI**: User interface should be dynamic and easy to use even from smartphones 
    and limited devices.


## Workflow ##

1.  App should parse the request and load the proper configurations and routing. It should behave like this:

    1.1. The hostname is checked to set the site/configuration to load. The routes are then loaded.

    1.2. Request is parsed and a route is searched for. When a valid route is found, credentials might be 
         requested.

        1.2.1. If web-cache is enabled, it might be checked now.

        1.2.3. If a download is in progress, it should be resumed (the same for HTTP streams). 

        1.2.2. If authentication is requested, the user is verified --no new cookies should be set at this point.

2. The response is rendered.

    2.1. Each page is made of content blocks --several might be addressed for each page, and each content 
        might be used in more than one page.

    2.2. A content block might interfere with the output headers and page layout. This should enable 
        Applications to output plain text and downloadables, as well as to behave like multiviews.

    2.3. After all contents are rendered, they are gathered and displayed by a Layout.

3. Output is forwarded to the client. The HTTP response ends. Post-execution routines start.

    3.1. E-mails and even database writes might be delegated to this step. Post-execution routines might last more
        than the requirements minimum.


## Interfaces ##

*   **App**: responsible for loading the environment and verifying the routes. If no route is found, the application 
    should return a clean response (nothing at all). It is expected that at least a catch-all, multiviewed 404 page is
    set.

*   **Route**: searches through routes-dir trying to find a route that matches the request. The verification is done
    at three levels:

    +   URL address should match the file name, from the most specific to the least: the request for /example/page.html
        tries the routes:
        - [$route-dir1]/example/page.html.yml
        - [$route-dir1]/example/page.yml
        - [$route-dir1]/example/.yml
        - [$route-dir1]/example.yml
        - [$route-dir1]/.yml

        - [$route-dir2]/example/page.html.yml
        - ...
        - [$route-dir2]/.yml

    +   When a route is found, it must match the formats (if available). If the route addresses a parent folder/file, 
        it must also have the "options: { multiviews: true }" set.
