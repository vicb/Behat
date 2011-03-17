# TRANSFORMERS


Feature: Named parameters

  Background:
    Given a file named "features/support/bootstrap.php" with:
      """
      <?php
      require_once 'PHPUnit/Autoload.php';
      require_once 'PHPUnit/Framework/Assert/Functions.php';
      """
    And a file named "features/steps/named_parameters.php" with:
      """
      <?php

      $steps->Given('/(?<first>\d+) before (?<second>\d+)/', function($world, $first, $second) {
          $world->first = $first;
          $world->second = $second;
      });

      $steps->Given('/(?<second>\d+) after (?<first>\d+)/', function($world, $first, $second) {
          $world->first = $first;
          $world->second = $second;
      });

      $steps->Then('/(\d+) comes before (\d+)/', function($world, $first, $second) {
          assertEquals($first, $world->first);
          assertEquals($second, $world->second);
      });
      """

  Scenario: Named parameters
    Given a file named "features/test.feature" with:
      """
      Feature: Natural order
        Scenario:
          Given 1 before 2
          Then 1 comes before 2

      Feature: Reverse order
        Scenario:
          Given 2 after 1
          Then 1 comes before 2

      """
    When I run "behat -f progress"
    Then it should pass with:
      """
      ....
      
      2 scenarios (2 passed)
      4 steps (4 passed)
      """
