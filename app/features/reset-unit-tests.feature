Feature: Password Reset
  Users can initiate a password reset, which stores a reset record in the database and sends an email with a
  unique URL for completing a password reset sequence.

  Scenario: Create a password reset for a user
    Given there is a user in the database
    And that user has a "manager_email" property value of "manager@example.com"
    When the user requests a password reset
    Then a reset record exists for the user
    And the reset record has a non-empty UUID
    And the reset record has an expiry in the future

  Scenario: Create a password reset for a user with no password recovery options
    Given there is a user in the database
    And the user has no password recovery methods
    And that user has a "manager_email" property value of "manager@example.com"
    When the user requests a password reset
    Then a "reset-self" email should be sent to their primary email
    And a "reset-on-behalf" email should be sent to "manager@example.com"
    And no other emails should be sent

  Scenario: Create a password reset for a user with no recovery options and no manager
    Given there is a user in the database
    And the user has no password recovery methods
    And the user has no manager email
    When the user requests a password reset
    Then a "reset-self" email should be sent to their primary email
    And no other emails should be sent

  Scenario: Create a password reset for a user with a password recovery email
    Given there is a user in the database
    And the user has a password recovery email "recovery@example.com"
    When the user requests a password reset
    Then a "reset-self" email should be sent to their primary email
    And a "reset-self" email should be sent to "recovery@example.com"
    And no other emails should be sent

  Scenario: Create a password reset for a user who has one already
    Given there is a user in the database with a valid password reset
    When the user requests a password reset
    Then a reset record exists for the user
    And the reset record has an expiry in the future
    And a "reset-self" email should be sent to their primary email

  Scenario: Create a password reset for a user who has an expired reset
    Given there is a user in the database with an expired password reset
    When the user requests a password reset
    Then a reset record exists for the user
    And the reset record has an expiry in the future
    And a "reset-self" email should be sent to their primary email

  Scenario: Create a password reset with the "include manager" option
    Given there is a user in the database with a valid password reset
    And that user has a "manager_email" property value of "manager@example.com"
    And the user has no password recovery methods
    When the user requests a password reset with "include manager" enabled
    Then a "reset-self" email should be sent to their primary email
    And a "reset-on-behalf" email should be sent to "manager@example.com"
    And no other emails should be sent

  Scenario: Verify a password reset
    Given there is a user in the database
    And the user has requested a password reset
    When the user submits the reset for verification
    Then the reset will become expired
