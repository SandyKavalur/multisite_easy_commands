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
   *   Example: --opt=foo=bar --opt=baz=qux
   * @option opts A comma-separated list of key-value pairs.
   *   Use quotes to pass an array of options.
   *   Example: --opts="uri=https://example.com,foo=bar,baz=qux"
   * @option add A comma-separated list of key-value pairs.
   *   Use quotes and separate url and site name by comma.
   *   Example: --add="https://example.com,example"
   *
   * @validate-module-enabled multisite_easy_commands
   * @usage mycommand "[<params>]" [--<opt>=<key>=<value>]...
   * @bootstrap full
   */
  public function multiSiteEasyCommands($params = '', $options = ['opt' => [], 'opts' => '', 'add' => '']) {
    $this->output()->writeln(
      "<options=bold>\nIf you are using commands and special characters like '$' and '-r, -d, -v, etc'. 
      Please add '\' before '-' and '$'.
      Eg: drush msl '\-r /var/www/html status'</>");
    // drush msl "cr" --opt="uri=http://example.com,foo=bar,baz=qux"
    
    // // get config data
    // $value = \Drupal::config('my_module.my_config_item')->get('my_config_value');

    // // set config data
    // \Drupal::config('my_module.my_config_item')
    //   ->set('my_config_value', $new_value)
    //   ->save();

    // Initialising variables
    $optionValues = '';
    $addSiteUrllist = [];
    $addSiteNamelist = [];
    
    $params = stripslashes($params);
    $set_drush_command = "drush " . $params;

    // Processing opts parameter
    if(!empty($options['opts']) && $options['opts'] !== TRUE){
      $getOptsCmds = explode(',', $options['opts']);
      $options['opt'] = array_merge($options['opt'], $getOptsCmds);
    }

    // Processing opt parameter
    if(!empty($options['opt'])){
      $optionPairs = $options['opt'];
      foreach ($optionPairs as $optionPair) {
        if (str_contains($optionPair, '=')) {
          list($optionName, $optionValue) = explode('=', $optionPair, 2);
          $optionValues .= '--' . $optionName . '=' . $optionValue . ' ';
        } else {
          $optionValues .= '--' . $optionPair . ' ';
        }
      }
    }

    // Processing add parameter
    if(!empty($options['add']) && $options['add'] !== TRUE){  
      if (str_contains($options['add'], ',')) {
        list($addSiteUrl, $addSiteName) = explode(',', $options['add'], 2);
      } else {
        $addSiteUrl = $options['add'];
        $addSiteName = $this->io()->ask('Please enter site name ');  
      }
      $addSiteUrllist[$addSiteUrl] = $addSiteName;
    } elseif (empty($options['add']) && $options['add'] == TRUE) {
      $addSiteUrl = $this->io()->ask('Please enter --uri ');
      $addSiteName = $this->io()->ask('Please enter site name ');
      $addSiteUrllist[$addSiteUrl] = $addSiteName;
    }
    // var_dump($addSiteUrllist);die;

    // Fetching config.yml
    // $pathToMyModule = \Drupal::service('extension.list.module')->getPath('multisite_easy_commands');
    $pathToMyModule = drupal_get_path('module', 'multisite_easy_commands');

    $config_file_path = $pathToMyModule . '/config/install/multisite_easy_commands.config.yml';
    $sites_copyfile_path = $pathToMyModule . '/fetch_sites.php';
    $config = Yaml::parseFile($config_file_path);
    $my_setting = $config['my_input'];
    
    // $config = ['my_input' => $params];
    // $yaml = Yaml::dump($config);
    // file_put_contents($pathToMyModule, $yaml);

    // Processing -l and --uri in command
    if (str_contains($params, '-l')) {
      $this->output()->writeln("<comment>\nFound '-l' in command, not modifying command. 
      Please remove it to select from list of sites</comment>");
      passthru($set_drush_command);
    } elseif (str_contains($params, '--uri')) {
      $this->output()->writeln("<comment>\nFound '--uri' in command, not modifying command.
      Please remove it to select from list of sites</comment>");
      passthru($set_drush_command);
    } else {

      // Fetch Sites from sites.php and config data
      $sites = MultiSiteEasyCommands::fetchSites($sites_copyfile_path);
      
      // Processing clear parameter
      if($options['clear']) {
        \Drupal::state()->delete('persist_url');
      }

      // Processing save parameter
      $persist_url = \Drupal::state()->get('persist_url', null);
      if ($persist_url !== NULL){
        $set_drush_command .= " --uri=" . $persist_url;
        $this->output()->writeln("<comment>\nUse --clear to clear memory and select different URL.</comment>");
      } else {
        $set_drush_command = MultiSiteEasyCommands::selectSiteFromList($sites, $set_drush_command, $options);
      }
      $set_drush_command .= ' ' . $optionValues;
      $this->output()->writeln("<info>Running drush command:</info> $set_drush_command \n");
      // IMPORTANT: DO NOT REMOVE BELOW LINE
      passthru($set_drush_command);
    }

  }

  public function fetchSites($sites_copyfile_path) {
    $this->output()->writeln("<comment>\nIf sites present in your root_DIR/sites/sites.php and are not shown here, 
    please check file access permission.</comment>");
    $sites = NULL;
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

  public function selectSiteFromList($sites, $set_drush_command, $options) {
    try {
      // $sites = NULL;
      if ($sites == NULL) {
        $this->output()->writeln("<comment>\nCouldn't find any sites, 
        Please enter site url to run the given command.</comment>");
        $user_input_url = $this->io()->ask('Please enter --uri ');
        $this->output()->writeln("");

        // if --save flag passed, then get url from state session
        if ($options['save']) {
          \Drupal::state()->set('persist_url', $user_input_url);
        }
        $selectedOption = $user_input_url;
      } else {
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
        
        $this->output()->writeln("<comment><options=bold;bg=yellow;fg=black>[Note]</> Press Enter to run command on main site.</comment>");
        $selectedOptionIndex = $this->io()->ask('Enter the number of your choice ');
        $selectedOption = $keys[$selectedOptionIndex - 1];
        
        $this->output()->writeln("<info>You selected:</info> $selectedOption");
        // if --save flag passed, then get url from state session
        if ($options['save']) {
          \Drupal::state()->set('persist_url', $selectedOption);
        }
      }
      $persist_url = \Drupal::state()->get('persist_url', null);
      if ($persist_url !== NULL){
        $set_drush_command .= " --uri=" . $persist_url;
      } elseif ($selectedOption !== NULL) {
        $set_drush_command .= " --uri=" . $selectedOption;
      }
      return $set_drush_command;
    } catch (Exception $e) {  
      $this->output()->write("<error>\nError: " . $e->getMessage() . "</error>");
      return $set_drush_command;
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
