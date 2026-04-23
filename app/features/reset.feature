Feature: Password Reset API
  A user can initiate a password reset, choose a delivery method, and
  validate the code they receive.

  Background:
    Given the user store is empty
      And the requester is authorized
      And I add a user with an "employee_id" of "123"

  Scenario: Create a new reset for an existing user
    Given I provide the following valid data:
        | property | value       |
        | username | john_smith  |
    When I request "/reset" be created
    Then the response status code should be 200
      And the response should contain a "uid" property
      And the response should contain a "methods" property
      And a reset record exists for user "123"

  Scenario: Create a reset using an email address as username
    When I provide the following valid data:
        | property | value                      |
        | username | john_smith@example.org     |
      And I request "/reset" be created
    Then the response status code should be 200

  Scenario: Cannot create a second reset for the same user
    Given I request "/reset" be created with username "john_smith"
    When I request "/reset" be created with username "john_smith"
    Then the response status code should be 200
      And only one reset record exists for user "123"

  Scenario: Retrieve a reset by uid
    Given a reset exists for user "123"
    When I send a "GET" to "/reset/{uid}" with a valid uid
    Then the response status code should be 200
      And the response should contain a "uid" property
      And the response should contain a "methods" property

  Scenario: Retrieve a non-existent reset returns 404
    When I send a "GET" to "/reset/aaaabbbbccccddddeeeeffffgggghhhh" with no uid substitution
    Then the response status code should be 404

  Scenario: Update reset type to supervisor
    Given a reset exists for user "123"
      And that user has a manager_email of "boss@example.org"
    When I provide the following valid data:
        | property | value      |
        | type     | supervisor |
      And I send a "PUT" to "/reset/{uid}" with a valid uid
    Then the response status code should be 200

  Scenario: Update reset type to method (requires a verified method)
    Given a reset exists for user "123"
      And user with employee id 123 has a verified Method "recovery@example.org"
    When I provide the following valid data:
        | property | value    |
        | type     | method   |
      And I also provide the method id in the request
      And I send a "PUT" to "/reset/{uid}" with a valid uid
    Then the response status code should be 200

  Scenario: Update reset type fails without a type parameter
    Given a reset exists for user "123"
    When I send a "PUT" to "/reset/{uid}" with a valid uid
    Then the response status code should be 400

  Scenario: Resend a reset verification email
    Given a reset exists for user "123"
    When I send a "PUT" to "/reset/{uid}/resend" with a valid uid
    Then the response status code should be 200

  Scenario: Validate reset code successfully
    Given a reset exists for user "123" with a known code
    When I provide the following valid data:
        | property | value      |
        | code     | RESETCODE1 |
      And I send a "PUT" to "/reset/{uid}/validate" with a valid uid
    Then the response status code should be 200
      And the reset record no longer exists

  Scenario: Validate reset code fails with wrong code
    Given a reset exists for user "123" with a known code
    When I provide the following valid data:
        | property | value       |
        | code     | WRONGCODE11 |
      And I send a "PUT" to "/reset/{uid}/validate" with a valid uid
    Then the response status code should be 400

  Scenario: Validate fails with missing code
    Given a reset exists for user "123"
    When I send a "PUT" to "/reset/{uid}/validate" with a valid uid
    Then the response status code should be 400

  Scenario: Validate expired reset returns 410 and restarts reset
    Given a reset exists for user "123" that is expired
    When I provide the following valid data:
        | property | value      |
        | code     | RESETCODE1 |
      And I send a "PUT" to "/reset/{uid}/validate" with a valid uid
    Then the response status code should be 410
      And a reset record still exists for user "123"

  Scenario: Too many attempts disables the reset
    Given a reset exists for user "123" with a known code
    When I submit too many incorrect codes for the reset
    Then the response status code should be 429

  Scenario: Create reset fails when user is not found
    When I provide the following valid data:
        | property | value             |
        | username | no_such_user_xyz  |
      And I request "/reset" be created
    Then the response status code should be 404

  Scenario: Create reset fails with missing username
    When I request "/reset" be created
    Then the response status code should be 400
