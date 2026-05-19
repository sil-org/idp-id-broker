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
    And that user has a "manager_email" property value of "manager@example.com"
    When the user requests a password reset
    Then a "reset-self" email should be sent to their primary email
    And a "reset-on-behalf" email should be sent to "manager@example.com"
    And no other emails should be sent

  Scenario: Create a password reset for a user with no recovery options and no manager
    Given there is a user in the database
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
