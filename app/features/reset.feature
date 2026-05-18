Feature: Password Reset API
  Users can initiate a password reset, which stores a reset record in the database and returns an API result that
  is not indicative of whether the user actually exists.

  Background:
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"

  Scenario: Successfully create a reset record for an existing user
    Given I prepare a request with the user's username
    When I request "/reset" be created
    Then the response status code should be 204
      And there is no response body

  Scenario: Successfully create a reset record given a user's email address
    Given I prepare a request with the user's email address given as their username
    When I request "/reset" be created
    Then the response status code should be 204
      And there is no response body

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

  Scenario: Correctly verifying a reset
    Given a user that has an existing reset record
    When I send a reset verification request using the correct uuid
    Then the response status code should be 200
    And the response should contain the employee_id of the user

  Scenario: Attempt to validate an expired reset
    Given a user that has an existing reset record
    And the reset has expired
    When I send a reset verification request using the correct uuid
    Then the response status code should be 410

  Scenario: Attempt to validate a reset using an invalid UUID
    Given a user that has an existing reset record
    When I send a reset verification request using an incorrect uuid
    Then the response status code should be 404
