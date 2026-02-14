
Feature: Integration with a live ID Store

  @integration @specificUser
  Scenario: Asking for a specific (active) user
    Given I can make authenticated calls to the ID Store
    When I ask the ID Store for a specific active user
    Then I should get back information about that user

  @integration @allUsers
  Scenario: Asking for all (active) users
    Given I can make authenticated calls to the ID Store
    When I ask the ID Store for all active users
    Then I should get back a list of information about active users

  @integration @usersChangedSince
  Scenario: Asking for (active) users changed since a particular time
    Given I can make authenticated calls to the ID Store
    When I ask the ID Store for all users changed since a specific point in time
    Then I should get back a list of information about changed users

  @integration @canUpdateLastSynced
  Scenario: Not updating last-synced when we simply ask the ID Store for a user
    Given I can make authenticated calls to the ID Store
      And I have a record of each user's last-synced value
      And those last-synced values are all in the past or empty
    When I ask the ID Store for a specific active user
    Then NONE of the users' last-synced values should have changed

  @integration @canUpdateLastSynced
  Scenario: Updating last-synced for a specific user
    Given I can make authenticated calls to the ID Store
      And I have a record of each user's last-synced value
      And those last-synced values are all in the past or empty
    When I update the last-synced value for a specific active user
    Then ONLY that user's last-synced value should have changed

  @integration @canUpdateLastSynced
  Scenario: Updating last-synced for all users
    Given I can make authenticated calls to the ID Store
      And I have a record of each user's last-synced value
      And those last-synced values are all in the past or empty
    When I update the last-synced value for every user
    Then every users' last-synced values should have changed

  @integration @canUpdateLastSynced
  Scenario Outline: Updating last-synced for some users
    Given I can make authenticated calls to the ID Store
      And I have a record of each user's last-synced value
      And those last-synced values are all in the past or empty
    When I update the last-synced values of users with a <field> of <value>
    Then ONLY last-synced values of users with a <field> of <value> should have changed

    Examples:
      | field  | value |
      | active | yes   |
      | active | no    |
