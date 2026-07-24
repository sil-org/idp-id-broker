Feature: Password
  In order to update the password of a specific user
  As an authorized user
  I need to be able to update a specific user password

  Scenario: Provide a new password for an existing user
    Given there is a user in the database
    When the user submits a new password
    Then a new password hash should be stored

  Scenario: Update the password hash cost
    Given there is a user in the database
      And that user has a password with a low hash cost
    When the user uses their password
    Then the password hash should be updated

  Scenario: Attempt to change a password to the one currently in use
    Given the user store is empty
      And the requester is authorized
      And I add a user with an employee_id of "10000"
      And I provide the following valid data:
        | property | value              |
        | password | k23@U$%235u25@I2$o |
      And I request "/user/10000/password" be updated
      And the response status code should be 200
    When I request "/user/10000/password" be updated
    Then the response status code should be 409
      And the response body should contain "May not be reused yet"

  Scenario: Attempt to change a password back to a previously used one
    Given the user store is empty
      And the requester is authorized
      And I add a user with an employee_id of "10000"
      And I provide the following valid data:
        | property | value              |
        | password | k23@U$%235u25@I2$o |
      And I request "/user/10000/password" be updated
      And the response status code should be 200
      And I provide the following valid data:
        | property | value              |
        | password | 8Wq@2v!zR6#tY4$mLp |
      And I request "/user/10000/password" be updated
      And the response status code should be 200
    When I provide the following valid data:
        | property | value              |
        | password | k23@U$%235u25@I2$o |
      And I request "/user/10000/password" be updated
    Then the response status code should be 409
      And the response body should contain "May not be reused yet"

  Scenario: Assessing a recently used password reports a conflict
    Given the user store is empty
      And the requester is authorized
      And I add a user with an employee_id of "10000"
      And I provide the following valid data:
        | property | value              |
        | password | k23@U$%235u25@I2$o |
      And I request "/user/10000/password" be updated
      And the response status code should be 200
    When I request "/user/10000/password/assess" be updated
    Then the response status code should be 409
      And the response body should contain "May not be reused yet"

  Scenario: A reuse limit of zero must not disable the reuse check
    Given there is a user in the database
      And the password reuse limit is 0
    When the user submits a new password
      And the user submits that same password again
    Then the password should be rejected as recently used

#  Scenario: Attempt to update a password for a nonexistent user
#  Scenario: Attempt to update a password for an existing user without providing a password
#  Scenario: Attempt to update a password for an existing user without providing a valid password
#
#  Scenario: Change the password for an existing user
#    Given I receive an existing employee id
#      And the requestor is authorized
#      And the user already has a password
#      And I receive a password
#    When I receive a request to change the password for a specific user
#    Then a new password hash should be stored
#      And the last changed date should be stored as the instant it was stored
#      And the last changed date should be stored in UTC
#      And the last synched date should be stored as the instant it was stored
#      And the last synched date should be stored in UTC
#      And the previous password hash should be saved in history
#
#  Scenario: Attempt to create a password
#  Scenario: Attempt to retrieve a password
#  Scenario: Attempt to delete a password
#  Scenario: consider invalid employee ids that test the type conversions of Yii and/or PHP, e.g., /user/[true|false|1|0]/password
#  Scenario: ensure password_hash and last_changed date are the only things that change even when passed all attributes available on the table.
# TODO: need to test the expiration date calculation
# TODO: need to test grace period and grace period extension
