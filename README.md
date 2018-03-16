# Recurring Contribution Search

![Screenshot](/screenshot.png)

The recurring contribution search adds a custom search to CiviCRM in which a user can search for recurring contributions. 
With recurring contributions we mean the agreement (such as donate every month 10 euro); not the actual contribution.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM 4.7+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl org.civicoop.recurringcontributionsearch@https://github.com/civicoop/org.civicoop.recurringcontributionsearch/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone @https://github.com/civicoop/org.civicoop.recurringcontributionsearch
cv en org.civicoop.recurringcontributionsearch
```

## Usage

You can find the custom search under Search --> Custom Searches --> Recurring Contribution Search
