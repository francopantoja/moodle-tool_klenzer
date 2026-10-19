<img src="pix/icon.svg" alt="Klenzer icon" width="96" height="96">

# Klenzer #

Klenzer keeps your Moodle user base clean. It automatically **suspends accounts that are no
longer used** and **deletes accounts that have never been used**, always through the standard
Moodle user API.

Developed by [IDEF](https://idef21.com), Certified Moodle Partner.

## Features ##

* **Suspend inactive accounts**: users who accessed the site at least once, but not during the
  configured number of years.
* **Delete never-used accounts**: users who never accessed the site, once the configured number
  of months has passed since the account was created.
* **Preview page**: lists the accounts that would be affected with the current settings, before
  anything is changed.
* **Simulation mode by default**: until *Live mode* is enabled, Klenzer records, logs and emails
  the summary without changing any account.
* **Daily scheduled task**, plus a manual *Run* button (with confirmation) in case the task fails.
* **Email summary** to a configurable list of recipients, one line per affected user.
* **Log file** in the site data directory (`tool_klenzer/klenzerlog.txt`), downloadable from the
  settings page.
* **History table** of every processed account (suspended / deleted, with date).
* **Privacy API** implemented (GDPR export and deletion).

Site administrators and the guest user are never affected.

## Requirements ##

* Moodle 5.2 or later.

## Installation ##

### Installing via uploaded ZIP file ###

1. Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code.
3. Check the plugin validation report and finish the installation.

### Installing manually ###

Copy the contents of this directory to

    {your/moodle/dirroot}/public/admin/tool/klenzer

Then log in to your Moodle site as an admin and go to _Site administration > Notifications_ to
complete the installation, or run

    $ php admin/cli/upgrade.php

## Configuration ##

Go to _Site administration > Users > Accounts > Klenzer settings_:

| Setting | Description | Default |
|---|---|---|
| Suspend after | Years without access before an account is suspended. | Never |
| Delete after | Months since creation before a never-used account is deleted. | Never |
| Notification recipients | Comma-separated email addresses that receive the summary. | Empty |
| Live mode | When enabled, accounts are actually suspended and deleted. | Disabled |

**Warning:** deletion may be irreversible and is the sole responsibility of the site. Make sure
you have the corresponding backups available before enabling *Live mode*.

## Usage ##

* _Site administration > Users > Accounts > Klenzer_ shows the preview of the affected
  accounts, the *Processed users* history and the *Run* button.
* The scheduled task *Process inactive users* runs daily at 03:xx. It can be configured in
  _Site administration > Server > Tasks > Scheduled tasks_, or run from the command line:

      $ php admin/cli/scheduled_task.php --execute='\tool_klenzer\task\process_inactive_users'

## Data storage ##

### Database table: `tool_klenzer` ###

History of the accounts processed by Klenzer. The actual name includes the site table prefix
(for example `mdl_tool_klenzer`).

| Field | Type | Null | Description |
|---|---|---|---|
| `id` | int(10), auto-increment | No | Primary key. |
| `userid` | int(10) | No | Affected user. Foreign key to `user.id`. |
| `state` | char(1) | No | Action applied: `s` = suspended, `d` = deleted. Indexed. |
| `timecreated` | int(10) | No | Unix timestamp of the action. Default `0`. |

The table is created on installation and dropped when the plugin is uninstalled. Its content is
covered by the Privacy API (export and deletion of user data).

### Plugin settings ###

Stored in the standard `config_plugins` table with `plugin = 'tool_klenzer'`:

| Name | Description |
|---|---|
| `suspendyears` | Years without access before suspension (`0` = never). |
| `deletemonths` | Months since creation before a never-used account is deleted (`0` = never). |
| `notifyemails` | Comma-separated notification recipients. |
| `livemode` | `1` = accounts are actually changed, `0` = simulation. |
| `welcomepending` | Internal flag used to show the welcome notice once after installation. |

### Log file ###

Each summary is appended to `{dataroot}/tool_klenzer/klenzerlog.txt`. The file can be downloaded
from the settings page.

## License ##

2026 by IDEF https://idef21.com

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
