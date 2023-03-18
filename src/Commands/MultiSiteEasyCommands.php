<?php

/**
 * @file
 * Drush commands made easy to work with multisite setup.
 */

namespace Drupal\multisite_easy_commands\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Site\Settings;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class MultiSiteEasyCommands extends DrushCommands {
  /**
   * Custom Drush commands made easy to work with multisite setup.
   *
   * @command msl
   * @param $arg1 Argument with drush command to be executed.
   * @option uppercase Uppercase the text
   * @aliases ccepm, cce-print-me
   */

  public function multiSiteEasyCommands($arg1 = 'hello world', $options = ['uppercase' => FALSE]) {
    if ($options['uppercase']) {
      $arg1 = strtoupper($arg1);
    }
    $this->output()->writeln($arg1);
    $helper = new QuestionHelper();
    
    $sites = [];
    
    // Get the path to the sites.php file.
    $sites_path = DRUPAL_ROOT . '/sites/sites.php';
    
    // Load the list of sites from sites.php.
    if (file_exists($sites_path)) {
      include_once $sites_path;
    }
    global $sites;

    // Printing Site options for user to select
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
  }
  
}



// $site_names = array();
// $sites = \Drupal::service('site.manager')->getSites();
// foreach ($sites as $site) {
//   $site_names[] = $site->getName();
// }


// $database = \Drupal::service('database');
// $sites = $database->query("SELECT DISTINCT value FROM {key_value} WHERE collection = 'state' AND name LIKE 'system.site.%'")->fetchCol();


// $siteConfigs = \Drupal::state()->getMultiple('system.site');
// $multiSiteNames = array_keys($siteConfigs);


// $siteManager = \Drupal::service('site_manager');
// $multiSiteNames = $siteManager->getSiteNames();


// $configFactory = \Drupal::service('config.factory');
// $multiSiteNames = array_keys($configFactory->listAll('system.site'));


// $siteManager = \Drupal::service('site_manager');
// $multiSiteNames = array_keys($siteManager->getSitePathMap());


// $multiSiteNames = \Drupal::state()->getMultiple(['system.site.']);
// $multiSiteNames = array_keys($multiSiteNames);


// $configFactory = \Drupal::service('config.factory');

// // Get an array of all multisite names.
// $multiSiteNames = [];
// $configItems = $configFactory->listAll('system.site');
// foreach ($configItems as $configName) {
//   if (strpos($configName, 'system.site.') === 0) {
//     $multiSiteNames[] = str_replace('system.site.', '', $configName);
//   }
// }


// $state = \Drupal::state();

// // Get an array of all multisite names.
// $multiSiteNames = [];
// $stateItems = $state->getMultiple(['system.site.']);
// foreach ($stateItems as $key => $value) {
//   $multiSiteNames[] = str_replace('system.site.', '', $key);
// }


// $database = \Drupal::database();

// // Get an array of all multisite names.
// $multiSiteNames = [];
// $query = $database->select('config', 'c');
// $query->fields('c', ['name']);
// $query->condition('name', 'system.site.%', 'LIKE');
// $results = $query->execute();
// foreach ($results as $result) {
//   $multiSiteNames[] = str_replace('system.site.', '', $result->name);
// }
// $sites_list = implode(', ', $multiSiteNames);
// $this->output()->writeln($sites_list . ' hi');


// $configFactory = \Drupal::configFactory();

// // Load the system.site configuration object.
// $systemSiteConfig = $configFactory->get('system.site');

// // Get the list of multisites.
// $multisites = array_filter($configFactory->listAll('system.site.'), function ($configName) use ($systemSiteConfig) {
//   return (strpos($configName, 'system.site.') === 0 && $configName !== 'system.site') && $configName !== $systemSiteConfig->getName();
// });

// // Output the list of multisites.
// foreach ($multisites as $multisite) {
//   $this->output()->writeln($multisite);
// }


// $siteAliasManager = \Drupal::service('site.alias_manager');

// // Get the list of site aliases.
// $siteAliases = $siteAliasManager->getSiteAliases();

// // Output the list of site aliases.
// foreach ($siteAliases as $siteAlias) {
//   $this->output()->writeln($siteAlias['name']);
// }



// $config_factory = \Drupal::configFactory();
// $config_names = $config_factory->listAll('system.site.');
// var_dump($config_names);


// $config_factory = \Drupal::configFactory();
// $config = $config_factory->getEditable('system.site.project-jarvis');
// $config_contents = $config->get();
// print_r($config_contents);