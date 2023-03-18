<?php

/**
 * @file
 * Drush commands made easy to work with multisite setup.
 */

namespace Drupal\multisite_easy_commands\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Site\Settings;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

use Drupal\Core\DrupalKernel;
use Drush\Exec\ExecTrait;
use Drush\Drush;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Consolidation\SiteAlias\SiteAliasManager;

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\ProcessFailedException;
/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
// /**
//  * Provides a custom Drush command for my module.
//  *
//  * @Plugin(
//  *   id = "multisite_easy_commands",
//  *   title = @Translation("My Custom Drush Command"),
//  *   description = @Translation("Runs a custom Drush command based on user input."),
//  *   arguments = {
//  *     "input" = @Drupal\Component\Annotation\Plugin\Argument(
//  *       type = "string",
//  *       label = @Translation("Input"),
//  *       description = @Translation("The input to process.")
//  *     )
//  *   }
//  * )
//  */
class MultiSiteEasyCommands extends DrushCommands {
  /**
   * Custom Drush commands made easy to work with multisite setup.
   *
   * @command msl
   * @aliases multi-site-list, cce
   * @param string $params A space-separated list of parameters.
   *   Use quotes to pass an array of parameters.
   * @option mslkey Key value option description.
   *
   * @validate-module-enabled multisite_easy_commands
   * @usage mycommand [<params>] [--<option_name>=<value>]...
   * @bootstrap full
   */

  public function multiSiteEasyCommands($params = '', array $options = []) {
    // Merge the user-defined options into the $options array.
    // $options = array_merge($options, $dynamic_options);
    // var_dump($params);
    $param_array = explode(' ', $params);
    if (str_contains($params, '-l')) {
      $this->output()->writeln("<comment>Found '-l' in command, not modifying command</comment>");
      passthru($params);
    } elseif (str_contains($params, '--uri')) {
      $this->output()->writeln("<comment>Found '--uri' in command, not modifying command</comment>");
      passthru($params);
    } else {
      $sites = [];
  
      // Get the path to the sites.php file.
      $sites_path = DRUPAL_ROOT . '/sites/sites.php';
  
      // Load the list of sites from sites.php.
      if (file_exists($sites_path)) {
        include_once $sites_path;
      }
      global $sites;
  
      // Printing Site options for user to select.
      $keys = array_keys($sites);
      $table = new Table(new ConsoleOutput());
      $table->setHeaders(['#', 'Site URL', 'Site Name']);
      foreach ($keys as $index => $key) {
        // var_dump($index, $option);
        $value = $sites[$key];
        $table->addRow([$index + 1, $key, $value]);
      }
      $table->render();
  
      $selectedOptionIndex = $this->io()->ask('Enter the number of your choice:');
      $selectedOption = $keys[$selectedOptionIndex - 1];
  
      $this->output()->writeln("<info>You selected:</info> $selectedOption");
  
      // Input selection received, starting drush command.
      $command = "drush ";
      foreach ($param_array as $index => $key) {
        $command .= $key . ' ';
      }
      $command .= "--uri=" . $selectedOption;
      passthru($command);
    }

  }
  
}


// * @param mixed[] $params 
// *   Array of arguments with drush command to be executed.
// * @param mixed[] $options
// *   An array of options.
// * @param mixed[] $dynamic_options
// *   An array of user-defined options and their values.
// *   Each key in the array is an option name, and the corresponding value
// *   is an array of option values.
// * @param mixed[] $args
// *   Any additional arguments passed in.

// array $params, array $options, array $dynamic_options = [], array $args = []


// array of params passing as string
//   drush mycommand "param1 param2 param3" --opt[foo]=bar --opt[baz]=qux --opt[abc]=def
