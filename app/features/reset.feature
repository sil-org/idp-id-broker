Feature: Password Reset API
  Users can initiate a password reset, which stores a reset record with a
  verification code in the database.

  Background:
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"

  Scenario: Successfully create a reset record for an existing user
    Given I provide the following valid data:
        | property    | value |
        | employee_id | 123   |
    When I request "/reset" be created
    Then the response status code should be 200
      And the following data is returned:
        | property | value |
        | type     | primary |
      And a reset record exists for employee "123"
      And the reset record has a non-empty uid
      And the reset record has a non-empty code

  Scenario: Attempt to create a reset without providing employee_id
    When I provide an empty request body
    And I request "/reset" be created
    Then the response status code should be 400

  Scenario: Attempt to create a reset for a non-existent user
    Given I provide the following valid data:
        | property    | value     |
        | employee_id | not-found |
    When I request "/reset" be created
    Then the response status code should be 404

  Scenario: Creating a reset for a user who already has a reset record returns the existing record
    Given I provide the following valid data:
        | property    | value |
        | employee_id | 123   |
    When I request "/reset" be created
    Then the response status code should be 200
      And the reset record has a non-empty uid
    Given I provide the following valid data:
        | property    | value |
        | employee_id | 123   |
    When I request "/reset" be created
    Then the response status code should be 200
      And the response uid matches the previously created reset
