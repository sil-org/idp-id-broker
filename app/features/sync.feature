
Feature: Synchronizing records

  # Ad-hoc synchronization scenarios:

  Scenario: User exists in both the ID Store and the ID Broker
    Given a specific user exists in the ID Store
      And the user exists in the ID Broker
    When I get the user info from the ID Store and send it to the ID Broker
    Then the user should exist in the ID Broker
      And we should have tried to update the ID Store's last-synced date for that user

  Scenario: User exists in the ID Store but not the ID Broker
    Given a specific user exists in the ID Store
      But the user does not exist in the ID Broker
    When I get the user info from the ID Store and send it to the ID Broker
    Then the user should exist in the ID Broker
      And the user info in the ID Broker and the ID Store should match
      And we should have tried to update the ID Store's last-synced date for that user

  Scenario: User exists in the ID Broker but not the ID Store
    Given a specific user exists in the ID Broker
      But the user does not exist in the ID Store
    When I learn the user does not exist in the ID Store and I tell the ID Broker
    Then the user should be inactive in the ID Broker
      And we should have tried to update the ID Store's last-synced date for that user

  Scenario: User does not exist in the ID Store or the ID Broker
    Given a specific user does not exist in the ID Store
      And the user does not exist in the ID Broker
    When I learn the user does not exist in the ID Store and I tell the ID Broker
    Then the user should not exist in the ID Broker

  Scenario: User info in ID Broker does not equal user info in ID Store
    Given a specific user exists in the ID Store
      And the user exists in the ID Broker
      And the user info in the ID Broker does not match the user info in the ID Store
    When I get the user info from the ID Store and send it to the ID Broker
    Then the user should exist in the ID Broker
      And the user info in the ID Broker and the ID Store should match

  Scenario: User has a manager email address in ID Broker but not in ID Store
    Given a specific user exists in the ID Store
      And the user exists in the ID Broker
      And the user has a manager email address in the ID Broker
      But the user does not have a manager email address in the ID Store
    When I get the user info from the ID Store and send it to the ID Broker
    Then the user should exist in the ID Broker
      And the user should not have a manager email address in the ID Broker


  # Full batch synchronization scenarios:

  Scenario: Update a user in the ID Broker
    Given ONLY the following users are active in the ID Store:
        | employeenumber | displayname  | username   | email                  | firstname | lastname | locked |
        | 10001          | Nickname     | first_last | first_last@example.com | Test      | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name | username   | active |
        | 10001          | First Last   | first_last | yes    |
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name | username   | active |
        | 10001          | Nickname     | first_last | yes    |
      And we tried to update the last-synced date in the ID Store for:
        | employeenumber |
        | 10001          |

  Scenario: Add a user to the ID Broker
    Given ONLY the following users are active in the ID Store:
        | employeenumber | displayname  | username   | email                  | firstname | lastname | locked |
        | 10001          | Person One   | person_one | person_one@example.com | Test1     | User     | no     |
        | 10002          | Person Two   | person_two | person_two@example.com | Test2     | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name | username   | active |
        | 10001          | Person One   | person_one | yes    |
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name | username   | active |
        | 10001          | Person One   | person_one | yes    |
        | 10002          | Person Two   | person_two | yes    |
      And we tried to update the last-synced date in the ID Store for:
        | employeenumber |
        | 10001          |
        | 10002          |

  Scenario: Handling a sync creation error gracefully
    Given 5 users are active in the ID Store
      And NO users exist in the ID Broker
      And user 3 in the list from ID Store will be rejected by the ID Broker
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And the ID Broker should now have 4 active users.
      And we tried to update the last-synced date in the ID Store for all 5 users EXCEPT user 3

  Scenario: Handling a sync update error gracefully
    Given 5 users are active in the ID Store and are inactive in the ID Broker
      And user 3 in the list from ID Store will be rejected by the ID Broker
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And the ID Broker should now have 4 active users.
      And we tried to update the last-synced date in the ID Store for all 5 users EXCEPT user 3

  Scenario: Handling sync errors gracefully (in more detail)
    Given ONLY the following users are active in the ID Store:
        | employeenumber | displayname     | username     | email          | firstname | lastname | locked |
        | 10001          | Good Update     | person_one   | p1@example.com | Test1     | User     | no     |
        | 10002          | Bad Create      | person_two   |                | Test2     | User     | no     |
        | 10003          | Bad Update      | person_three |                | Test3     | User     | no     |
        | 10004          | Good After Bad  | person_four  | p4@example.com | Test4     | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name    | username     | email          | active |
        | 10001          | One to Update   | person_one   | p1@example.com | yes    |
        | 10003          | Three to Update | person_three | p3@example.com | yes    |
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name    | username     | email          | active |
        | 10001          | Good Update     | person_one   | p1@example.com | yes    |
        | 10003          | Three to Update | person_three | p3@example.com | yes    |
        | 10004          | Good After Bad  | person_four  | p4@example.com | yes    |
      And we ONLY tried to update the last-synced date in the ID Store for the following:
        | employeenumber |
        | 10001          |
        | 10004          |

  Scenario: Activate a user in ID Broker
    Given ONLY the following users are active in the ID Store:
        | employeenumber | displayname  | username   | email                  | firstname | lastname | locked |
        | 10001          | Person One   | person_one | person_one@example.com | Test1     | User     | no     |
        | 10002          | Person Two   | person_two | person_two@example.com | Test2     | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name | username   | active |
        | 10001          | Person One   | person_one | yes    |
        | 10002          | Person Two   | person_two | no     |
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name | username   | active |
        | 10001          | Person One   | person_one | yes    |
        | 10002          | Person Two   | person_two | yes    |
      And we tried to update the last-synced date in the ID Store for:
        | employeenumber |
        | 10001          |
        | 10002          |

  Scenario: Deactivate a user in ID Broker
    Given ONLY the following users are active in the ID Store:
        | employeenumber | displayname  | username     | email                  | firstname | lastname | locked |
        | 10002          | Person Two   | person_two   | person_two@example.com | Test1     | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name | username     | active |
        | 10001          | Person One   | person_one   | yes    |
        | 10002          | Person Two   | person_two   | yes    |
        | 10003          | Person Three | person_three | no     |
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
      And ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name | username     | active |
        | 10001          | Person One   | person_one   | no     |
        | 10002          | Person Two   | person_two   | yes    |
        | 10003          | Person Three | person_three | no     |
      And we tried to update the last-synced date in the ID Store for:
        | employeenumber |
        | 10001          |
        | 10002          |
        | 10003          |

  # Incremental batch synchronization scenarios:

  Scenario: Syncing users changed since a specific point in time
    Given the ID Store has the following log of when users were changed:
        | changedat   | employeenumber |
        | 1491400000  | 10001          |
        | 1491400700  | 10003          |
        | 1491400800  | 10002          |
        | 1491400900  | 10004          |
      And ONLY the following users are active in the ID Store:
        | employeenumber | displayname    | username     | email                | firstname | lastname | locked |
        | 10001          | Unchanged User | person_one   | person_1@example.com | Test1     | User     | no     |
        | 10002          | Changed User   | person_two   | person_2@example.com | Test2     | User     | no     |
        | 10004          | Added User     | person_four  | person_4@example.com | Test4     | User     | no     |
        | 10005          | Missed User    | person_five  | person_5@example.com | Test5     | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name   | username     | active |
        | 10001          | Unchanged User | person_one   | yes    |
        | 10002          | User To Change | person_two   | yes    |
        | 10003          | Removed User   | person_three | yes    |
    When I ask the ID Store for the list of users changed since 1491400600 and sync them
    Then ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name   | username     | active |
        | 10001          | Unchanged User | person_one   | yes    |
        | 10002          | Changed User   | person_two   | yes    |
        | 10003          | Removed User   | person_three | no     |
        | 10004          | Added User     | person_four  | yes    |
      And we tried to update the last-synced date in the ID Store for:
        | employeenumber |
        | 10002          |
        | 10003          |
        | 10004          |

  Scenario: Syncing users changed since a specific point in time despite a sync error
    Given the ID Store has the following log of when users were changed:
        | changedat   | employeenumber |
        | 1491401000  | 10001          |
        | 1491402000  | 10002          |
        | 1491403000  | 10003          |
      And ONLY the following users are active in the ID Store:
        | employeenumber | displayname    | username     | email          | firstname | lastname | locked |
        | 10001          | Unchanged 1    | person_one   | p1@example.com | Test1     | User     | no     |
        | 10002          | Changed 2      | person_two   |                | Test2     | User     | no     |
        | 10003          | Changed 3      | person_three | p3@example.com | Test3     | User     | no     |
      And ONLY the following users exist in the ID Broker:
        | employee_id    | display_name   | username     | email          | active |
        | 10001          | Unchanged 1    | person_one   | p1@example.com | yes    |
        | 10002          | Original 2     | person_two   | p2@example.com | yes    |
        | 10003          | Original 3     | person_three | p3@example.com | yes    |
    When I ask the ID Store for the list of users changed since 1491401999 and sync them
    Then ONLY the following users should exist in the ID Broker:
        | employee_id    | display_name   | username     | email          | active |
        | 10001          | Unchanged 1    | person_one   | p1@example.com | yes    |
        | 10002          | Original 2     | person_two   | p2@example.com | yes    |
        | 10003          | Changed 3      | person_three | p3@example.com | yes    |
      And we ONLY tried to update the last-synced date in the ID Store for the following:
        | employeenumber |
        | 10003          |


  Scenario: User has a manager email address in ID Broker but ID Store does not provide it
    Given ONLY the following users are active in the ID Store:
      | employeenumber | displayname  | username     | email          | firstname | lastname | locked |
      | 10001          | Person One   | person_one   | p1@example.com | Test1     | User     | no     |
    And ONLY the following users exist in the ID Broker:
      | employee_id    | display_name | username   | manager_email     | active |
      | 10001          | Person One   | person_one | boss1@example.org | yes    |
    When I sync all the users from the ID Store to the ID Broker
    Then an exception should NOT have been thrown
    And ONLY the following users should exist in the ID Broker:
      | employee_id    | display_name | username     | manager_email | active |
      | 10001          | Person One   | person_one   |               | yes    |

  Scenario: Sending a notification
    Given at least one user has no email address
    When I call the sendMissingEmailNotice function
    Then an email is sent

  Scenario: Don't send missing email notification when the ID Store user has an email address
    Given a specific user exists in the ID Store with an email address
    But the user does not exist in the ID Broker
    When I get the user info from the ID Store and send it to the ID Broker
    Then an email with subject "Email address missing" is not sent

  Scenario: Missing email notification
    Given a specific user exists in the ID Store without an email address
    But the user does not exist in the ID Broker
    When I get the user info from the ID Store and send it to the ID Broker
    Then an email is sent
    And the email subject contains "Email address missing"

  Scenario: New user email notification - enabled
    Given new user email notifications are enabled
    And a specific user exists in the ID Store
    But the user does not exist in the ID Broker
    When I get the user info from the ID Store and send it to the ID Broker
    Then a "New user" email is sent to the user's HR contact

  Scenario: New user email notification - disabled
    Given new user email notifications are disabled
    And a specific user exists in the ID Store
    But the user does not exist in the ID Broker
    When I get the user info from the ID Store and send it to the ID Broker
    Then an email with subject "New user" is not sent
