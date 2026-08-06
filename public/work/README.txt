e360TV Shared PHP Checklist
============================

FILES
-----
index.php
api.php
config.php
private/.htaccess
private/e360tv-checklist-state.json

INSTALLATION
------------
1. Upload the entire folder to a PHP-enabled location on the server.
2. Keep the folder structure intact.
3. Open config.php.
4. Optional but strongly recommended:
       $CHECKLIST_PASSWORD = 'choose-a-team-password';
5. Make sure PHP can write to:
       private/
       private/e360tv-checklist-state.json
   Typical permissions are 755 for folders and 664 for the JSON file.
   Some hosting accounts may require 775 for the private folder.
6. Open index.php in a browser.

SHARED STATE
------------
- Statuses, notes, editor names and update times are saved in:
      private/e360tv-checklist-state.json
- Every visitor sees the same server-side state.
- Changes to different tasks are merged safely.
- Simultaneous changes to the same task use the last saved change.
- The page refreshes shared state every 30 seconds when nobody is typing.
- The Reset All action creates a timestamped JSON backup first.

SECURITY
--------
- The included private/.htaccess blocks direct web access on Apache/LiteSpeed.
- If the server uses Nginx, add a rule denying web access to the /private directory.
- Set the optional password in config.php when the URL is not already protected.
- Use HTTPS.
- Do not place this package on the compromised old server unless that environment has
  been rebuilt and independently verified clean.

TROUBLESHOOTING
---------------
"Unable to write the state file":
- Check ownership and permissions for the private folder and JSON file.

"Unauthorized":
- Reload index.php and enter the configured password.

Changes do not appear for another person:
- Click Refresh Shared State or allow up to 30 seconds for automatic refresh.
