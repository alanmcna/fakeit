<?php

/**
 * Mysql dump using Faker to sanitise personal data on the fly
 *
 */

 $_SERVER['SERVER_NAME'] = 'localhost';
 require_once('environment.php');

 include_once(dirname(__FILE__) . '/Ifsnop/Mysqldump/Mysqldump.php');

 # Load Fakers own autoloader
 require_once dirname(__FILE__) . '/Faker/src/autoload.php';

 // ******** SCRIPT *********

 // --- Get all opts
 $opts = getopt('', [ 
    'tables:',        // Include these tables only
 ]);

  // Always compress the output
  $dumperSettings = array( 'compress' => Ifsnop\Mysqldump\Mysqldump::GZIP );

  // Check opts for specific tables
  if ( array_key_exists('tables', $opts) ) 
     $dumperSettings[ 'include-tables' ] = explode(',', $opts['tables']);

  // Set-up our dumper object/connection
  $dumper = new Ifsnop\Mysqldump\Mysqldump('mysql:host='.$_config->dbHost.';dbname='.$_config->dbName, $_config->dbUsername, $_config->dbPassword, $dumperSettings );
var_dump($dumper);exit;
  // Create a Faker object
  $faker = Faker\Factory::create();
  $faker->addProvider(new Faker\Provider\en_NZ\Internet($faker));
  $faker->addProvider(new Faker\Provider\en_NZ\Address($faker));
  $faker->addProvider(new Faker\Provider\en_NZ\PhoneNumber($faker));

  // check DB fields for Faker method via
  // SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.columns WHERE COLUMN_NAME LIKE '%mobile%'
  // -- set appropriately as per https://github.com/fzaninotto/Faker

  $dumper->setTransformColumnValueHook(function ($tableName, $colName, $colValue) {

        global $faker;

        if ( strlen(trim($colValue)) <= 0 || ! is_string($colValue) ) {
          return $colValue;
        } elseif ( strpos($colName, 'first_name') !== FALSE) {
          return (string) $faker->firstName(); 
        } elseif ( strpos($colName, 'last_name') !== FALSE) {
          return (string) $faker->lastName; 
        } elseif ( strpos($colName, 'company_name') !== FALSE) {
          return (string) $faker->company; 
        } elseif ( strpos($colName, 'email') !== FALSE && strpos($colName, 'is_email_valid') === FALSE ) {
          return (string) $faker->safeEmail; // could use email, safeEmail, freeEmail, companyEmail
        } elseif ( strpos($colName, 'phone') !== FALSE ) {
          return (string) $faker->phoneNumber; 
        } elseif ( strpos($colName, 'mobile') !== FALSE && strpos($colName, 'country_code') === FALSE ) {
          return (string) $faker->mobileNumber; 
        } elseif ( strpos($colName, 'postal_address') !== FALSE   || 
                   strpos($colName, 'owner_address') !== FALSE    || 
                   strpos($colName, 'driver_address') !== FALSE ) {
          return (string) $faker->streetAddress; 
        } elseif ( strpos($colName, 'postcode') !== FALSE) {
          return (string) $faker->postcode; 
        }

        return $colValue;
  });

  // dump the results
  $dumper->start($_config->dbName . '-sanitised.sql.gz');
