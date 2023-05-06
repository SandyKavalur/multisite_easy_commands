# Multi Site Easy Commands

This is a Custom Drush commands module which helps in running drush commands
for a multisite setup. This module provides two ways to add sites url, a
configuration form and through terminal.<br>
<br>
This module eliminates the need to copy site Uri everytime you need to
run a multisite drush command. *MSL* is short for *Multi Site list*.<br>
<br>
It is recommended to install the module in main project site, else you will
have to run the command with the *--uri* option everytime.<br>
<br>
Command format is **drush msl "\<params>" [--\<opt>=\<key>=\<value>]...**<br>
<br>
Once you selected the Uri, module runs the provided command on the selected
site. If you want to save the selection in terminal session, use the
**--save** option and if you want to clear the selection in terminal
session, use the **--clear** option. 


## Requirements

This module requires the following modules:

- [Drush](https://www.drupal.org/project/drush)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Enable the module at Administration > Extend.
2. Click on the configure button on module description, or visit
    '/admin/config/msl-configuration' path.
3. Click on Add more button to add site url and name.


## Usage

    drush msl "<params>" [--<opt>=<key>=<value>]...


### Parameters

- **$params** (string): A space-separated list of parameters. Use quotes to 
    pass an array of parameters and backslash to escape special characters.<br>
    Example: **drush msl "\-r \-l \-v etc..."** or **drush msl cr**
- **$options** (array): An array of options to use during processing.


### Options

- **--save**: Select a site to save (use this site as default).
- **--clear**: Clear the site saved as default.
- **--remove**: Remove the site from config data.
- **--opt**: A key-value pair of drush command.<br>
    Example: **--opt=foo=bar --opt=baz=qux**.
- **--opts**: A comma-separated list of key-value pairs. Use quotes to pass an 
    array of options.<br>
    Example: **--opts="foo=bar,baz=qux"**.
- **--add**: A comma-separated list of key-value(site_uri,site_name) pairs. Use 
    quotes and separate url and site name by comma. Or just **--add**.<br>
    Example: **--add="https://example.com,example"**.
