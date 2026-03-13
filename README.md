# How to use the things:
* Checkout the master branch on an environment that already has the web stuff installed and running for you (apache, php) (another assumption here is whoever is testing this has that ready)
    * `git clone -b master https://github.com/nick-schuller/flosports.git`
* The sqlite database was uploaded in master so it should already be using everything. The .gitignores were commented out of course so all the files _should_ be in there and ready to go.
* Start it up using `php artisan serve`
* Run tests using `php artisan test`
* There's a postman collection added to the base directory too to make things easier as far the naming convention goes and testing things out located here: https://github.com/nick-schuller/flosports/blob/master/Flosports%20WatchSessionTracker.postman_collection.json
* The endpoints are as follows if you don't want to use the postman collection are below. When you run the `serve` command to start the server, it will tell you what the address will be (localhost with a port, typically http://localhost:8000)
    * Ingestion
        * **POST** `/v1/events`
    * Get active session count for an event
        * **GET** `/v1/watch-sessions/active-count/{eventId}`
    * Get session details for a given session ID
        * **GET** `/v1/watch-sessions/{sessionId}`
* JSON payload to use from provided PRD:
  ```json
    {
        "sessionId": "abc-123",
        "userId": "user-456",
        "eventType": "heartbeat",
        "eventId": "evt-789",
        "eventTimestamp": "2026-02-10T19:32:15.123Z",
        "receivedAt": "2026-02-10T19:32:15.450Z",
        "payload": {
            "eventId": "event-2026-wrestling-finals",
            "position": 1832.5,
            "quality": "1080p"
        }
    }
    ```

# Assumptions: 
* I have no idea whether or not the eventId in the base event is supposed to match up with the eventId in the payload (or maybe it is supposed to be a payloadId?), so I ended up using the eventId from the base event when creating both the regular event and the session. I assumed that perhaps
they were supposed to be the same, and if not maybe the key for the name was supposed to be different? Or maybe they came from two different tables? Or maybe were just two different resources (or maybe it's maybelline?).
* I assumed that all of the event types were listed at the time of writing, although the switch in the code leaves it pretty easy to add new ones in, despite this not being the best way to really do that directly in the controller.
* I didn't write any code to automatically set a session as inactive if it has been sitting and hasn't received a heartbeat for a certain amount of time. We'd probably want to add in a job (laravel) or a message (symfony) to look through and de-activate sessions that have sat for too long, or even make like a mysql event and schedule it to run periodically. I see in the instructions it says that we're going to receive a heartbeat every 30 seconds but the code essentially ignores that right now. Perhaps the code that checks the active session count should been related to that as well.

  
# Tools and resources: 
* I used Visual Studio Code with a handful of extensions - Some themes that I already had in there, PHP intelephense, some syntax highlighting for Laravel / twig / Symfony (though I didn't end up going the Symfony route).
* I also utilized an Ubuntu 24.04 Server VM that I already had up and running with some other Symfony / Laravel and LAMP stuff installed on it to make it a bit faster, that way I didn't have to install PHP and whatnot on my main Windows box.
* In terms of AI usage, I relied heavily on ChatGPT for some of the quick "write me a controller for these requirements", "construct some routes and models for the same requirements". It created the majority of the boilerplate code and I did some tweaking
here and there. It also did some rough drafts for the phpunit tests. All the git stuff was just command line on the Ubuntu box and connected to my github. 


# Trade-offs:
* There should be a lot of things changed in the code from how it currently exists. As there was a pretty short timeframe here for development, I glazed over a lot of naming conventions that could better, as well as folder structure and endpoint naming that could probably be better. As far as
data types and whatnot, I mostly just accepted what was given from AI in those cases to save time and resolved a few small runtime errors for messed up names or unexpected types during debugging. The PHP unit stuff the AI spit out also wasn't perfect as I generally don't expect it to be, and I needed to tweak it
here and there. I tried using windsurf a bit for this and some of the inline copilot stuff, but it wasn't really giving me what I wanted so I quickly switched to a different tool (ChatGPT) to move things along. Of course I could've spent time trying to finagle the vscode plugins to make things work properly, but
there wasn't a ton of time allotted for this and I get that was also the point.
* Definitely could add in jobs like I mentioned to set certain sessions as inactive.
* Could move the business logic for what to do with a given event to a service to decouple things better.
* Could likely have better validation in place and not use a list of strings for the eventType. At the very least have an array const that can just be plugged in there or something similar, perhaps change to an enumeration.
* The ingestion system doesn't have any kind of retries in place, throttling in the event we get spammed or ddos'd
* I thought about just storing stuff in a file and doing some comma delimited lists or something, but then I thought to myself the sqlite db that's just included with a laravel project init would probably be easier. That's also why I didn't
use Symfony in this case, though I could've just as easily created the db myself but I'm lazy (I realize it just takes a couple commands to do that in Symfony but..ehhhh, I was only given 2 hours here!).
* All of the API endpoints should have some kind of authentication on them and not allow just blind submission
* Could have done better with sanitization of input from the front end instead of relying fully on the validator. This is typically handled anyway by Laravel but I like to be thorough.
* Essentially, to keep this as uncomplex as possible, a lot of the things mentioned above were scrapped in favor of a working PoC. Having all 3 of those things engineered perfectly within 2 hours is impossible, at least to the degree that I feel would properly satisfy everyone. Realistically we'd want Redis to
more efficiently handle some of the data and lower the latency (with persisting that data into storage with a job, possibly - just spitballing really), more usage of caching mechanisms to properly handle resubmissions of events and sessions. We'd want to add in some type of replication to try and satisfy the
operational stakeholders, but getting that up and running would mean adding complexity as well. AWS EC2 instances with load balancing and replication would be nice as well. That way you can also scale more easily if need be and keep high uptime, but that's on a full functioning prod system and not a simple lightweight PoC like this.

# Final Thoughts
The time says about 2 hours, I think I roughly used maybe 3 hours total, a good hour or so was spent on typing up my thoughts after the fact and I think I was really close to 2 hours as far as the coding goes. The commit history timeline shows more time than that I imagine, but I took a lot of breaks in between - was doing laundry, handling an emergency with my Dad who lives in TN. So there are pretty long chunks of time between some commits where I wasn't actually at my desk working on things. I wanted to make sure to provide a good commit history that was all together. I could've rebased or edited the history but I wanted to just leave it as plain as possible. I added in some things like agent skills that I didn't end up using at all from Laravel Boost (I've never used those before and thought it might be fun, but it would've taken way too much time). I thought it was interesting that the engineering for stakeholders care about a slim,
lead, clean system without complexity at all but operation and product wants a very robust, high uptime system


## Here are some additional notes I took while running into a few installation related things and how I resolved them:

* Issue: Couple startup issues with just versioning of some files. "npm install" and "build" didn't want to work because of node version installed on my virtual machine.
  * Resolution: Used ChatGPT to quickly resolve the errors and explain the errors and provide the quick command for the solutions. These included
super easy fixes to node version not being high enough for Laravel Vite (needed at least version 20). Installed the nvm (node verison manager) and ran a quick "nvm install 22" to get that resolved,
npm install && npm run build executed successfully afterwards.

* Issue: Laravel starts with a sqlite db which I didn't have installed on my vm yet (previously just used MySQL when mucking around but that's too heavy for this).
  * Resolution: Needed to just install "php-sqlite3" through apt
* Issue: sqlite db is setup now but migrations needed to be run to create the initial tables in the sqlite db (default connection)
  * Resolution: Ran "php artisan migrate". Successfully ran through the basic user, cache, jobs table that come bundled with the setup.
