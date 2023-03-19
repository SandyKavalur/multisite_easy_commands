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
use Symfony\Component\Console\Input\InputInterface;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\State\StateInterface;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
   * @option save Key value option description.
   * @option clear Key value option description.
   * @option opt A comma-separated list of key-value pairs.
   *   Use quotes to pass an array of options.
   *   Example: --opt="url=https://example.com,foo=bar,baz=qux"
   *
   * @validate-module-enabled multisite_easy_commands
   * @usage mycommand "[<params>]" [--<opt>=<key>=<value>]...
   * @bootstrap full
   */
  public function multiSiteEasyCommands($params = '', $options = ['opt' => []]) {
    // drush msl "cr" --opt="url=http://example.com,foo=bar,baz=qux"
    if($options['opt'] !== NULL){
      $optionPairs = $options['opt'];
      $optionValues = [];
      foreach ($optionPairs as $optionPair) {
        list($optionName, $optionValue) = explode('=', $optionPair, 2);
        $optionValues[$optionName] = $optionValue;
      }
      var_dump($optionValues);
    }

    // $pathToMyModule = \Drupal::service('extension.list.module')->getPath('multisite_easy_commands');
    $pathToMyModule = drupal_get_path('module', 'multisite_easy_commands');

    $config_file_path = $pathToMyModule . '/config/install/multisite_easy_commands.config.yml';
    $sites_copyfile_path = $pathToMyModule . '/fetch_sites.php';
    $config = Yaml::parseFile($config_file_path);
    $my_setting = $config['my_input'];
    // var_dump($params);die;
    
    // $config = ['my_input' => $params];
    // $yaml = Yaml::dump($config);
    // file_put_contents($pathToMyModule, $yaml);

    if (str_contains($params, '-l')) {
      $this->output()->writeln("<comment>Found '-l' in command, not modifying command. 
      Please remove it to select from list of sites</comment>");
      passthru($params);
    } elseif (str_contains($params, '--uri')) {
      $this->output()->writeln("<comment>Found '--uri' in command, not modifying command.
      Please remove it to select from list of sites</comment>");
      passthru($params);
    } else {
      // Fetch Sites from sites.php and config data
      $sites = multiSiteEasyCommands::fetchSites($sites_copyfile_path);
      
      if($options['clear']) {
        \Drupal::state()->delete('persist_url');
      }

      $persist_url = \Drupal::state()->get('persist_url', null);
      if ($persist_url !== NULL){
        $param_array = explode(' ', $params);
        // $command = "drush " . $params;
        $command = "drush ";
        foreach ($param_array as $index => $key) {
          $command .= $key . ' ';
        }
        $command .= "--uri=" . $persist_url;
        $this->output()->writeln("<comment>Use --clear to clear memory and select different URL.</comment>");
      } else {
        $command = multiSiteEasyCommands::selectSiteFromList($sites, $params, $options);
      }
      // IMPORTANT: DO NOT REMOVE BELOW LINE
      passthru($command);
    }

  }

  public function fetchSites($sites_copyfile_path) {
    if (!is_readable($sites_path)) {
      $this->output()->writeln("<comment>If sites present in your root_DIR/sites/sites.php and are not shown here, 
      please check file access permission.</comment>");
    }
    $sites_path = DRUPAL_ROOT . '/sites/sites.php';
    if (file_exists($sites_path)) {
      $contents = file_get_contents($sites_path);
      if (!file_exists($sites_copyfile_path)) {
        $sitesFileHandle = fopen($sites_copyfile_path, 'w') or die("can't open file");
        fclose($sitesFileHandle);
      }
      file_put_contents($sites_copyfile_path, $contents);
    }
    include_once $sites_copyfile_path;
    $sites = $sites;
    unlink($sites_copyfile_path);
    // var_dump($sites);die;
    return $sites;
  }

  public function selectSiteFromList($sites, $params, $options) {
    try {
      if ($sites == NULL) return "drush ";
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
      $param_array = explode(' ', $params);
      // $command = "drush " . $params;
      $command = "drush ";
      foreach ($param_array as $index => $key) {
        $command .= $key . ' ';
      }

      // if --save flag passed, then get url from state session
      if ($options['save']) {
        \Drupal::state()->set('persist_url', $selectedOption);
      }
      $persist_url = \Drupal::state()->get('persist_url', null);
      if ($persist_url !== NULL){
        $command .= "--uri=" . $persist_url;
      } else {
        $command .= "--uri=" . $selectedOption;
      }
      return $command;
    } catch (Exception $e) {  
      $this->output()->write("<error>Error: " . $e->getMessage() . "</error>");
      return "drush ";
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

// function myfunc(array $params, array $options, array $dynamic_options = [], array $args = [])
// Merge the user-defined options into the $options array.
// $options = array_merge($options, $dynamic_options);

// array of params passing as string
//   drush mycommand "param1 param2 param3" --opt[foo]=bar --opt[baz]=qux --opt[abc]=def
