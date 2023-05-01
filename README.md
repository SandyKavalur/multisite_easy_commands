# multisite_easy_commands
Drush commands made easy to work with multisite setup.

/**
   * Custom Drush commands made easy to work with multisite setup.
   *
   * @command msl
   * @aliases multi-site-list
   * @param string
   *   Use quotes to pass an array of parameters and backslash to escape special characters.
   *   Example: drush msl "\-r \-l \-v etc..."
   * @option save Select a site to save(use this site as default).
   * @option clear Clear the site saved as default.
   * @option remove Remove the site from config data.
   * @option opt A key-value pair of drush command.
   *   Example: --opt=foo=bar --opt=baz=qux
   * @option opts A comma-separated list of key-value pairs.
   *   Use quotes to pass an array of options.
   *   Example: --opts="uri=https://example.com,foo=bar,baz=qux"
   * @option add A comma-separated list of key-value(site_uri,site_name) pairs.
   *   Use quotes and separate url and site name by comma. Or just --add.
   *   Example: --add="https://example.com,example"
   *
   * @usage mycommand "[<params>]" [--<opt>=<key>=<value>]...
   */
