Feature: Workday ID Store
  As an administrator
  I want the Workday ID Store to correctly identify itself
  And format user group lists based on various configurations

  Scenario: Get the ID store name
    Given a Workday ID Store with default configuration
    Then the ID store name should be "Workday"

  Scenario: Generate groups list with default configuration
    Given a Workday ID Store with default configuration
    When I generate group lists for the following users:
      | company_ids | ou_tree |
      | a b c       | d e f   |
    Then user 0 should have the groups "a,b,c,d,e,f"

  Scenario: Generate groups list with custom configuration
    Given a Workday ID Store with configuration:
      | groupsFields    | field1,field2 |
    When I generate group lists for the following users:
      | field1 | field2 |
      | x y z  | 1 2 3  |
    Then user 0 should have the groups "x,y,z,1,2,3"

  Scenario: Generate groups list with missing Company IDs
    Given a Workday ID Store with default configuration
    When I generate group lists for the following users:
      | ou_tree |
      | d e f   |
    Then user 0 should have the groups "d,e,f"

  Scenario: Generate groups list with missing OU Tree
    Given a Workday ID Store with default configuration
    When I generate group lists for the following users:
      | company_ids |
      | a b c       |
    Then user 0 should have the groups "a,b,c"
