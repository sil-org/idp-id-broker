Feature: Password Reset API
  Users can initiate a password reset, which stores a reset record with a
  verification code in the database.

  Background:
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"

  Scenario: Successfully create a reset record for an existing user
    Given I prepare a request with the user's username
    When I request "/reset" be created
    Then the response status code should be 204
      And there is no response body
      And a reset record exists for employee "123"
      And the reset record has a non-empty UUID
      And the reset record has an expiry in the future

  Scenario: Successfully create a reset record given a user's email address
    Given I prepare a request with the user's email address given as their username
    When I request "/reset" be created
    Then the response status code should be 204
      And there is no response body
      And a reset record exists for employee "123"
      And the reset record has a non-empty UUID
      And the reset record has an expiry in the future

  Scenario: Attempt to create a reset without providing employee_id
    When I provide an empty request body
    And I request "/reset" be created
    Then the response status code should be 400

  Scenario: Attempt to create a reset for a non-existent user
    Given I provide the following data:
        | property | value     |
        | username | not-found |
    When I request "/reset" be created
    # Respond identically whether the user exists or not, to hide which users exist.
    Then the response status code should be 204

  Scenario: Creating a reset for a user who already has a reset record
    Given a user that has an existing reset record
      And I prepare a request with the user's username
    When I request "/reset" be created
    Then the response status code should be 204
