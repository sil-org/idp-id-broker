Feature: SentryHelper Unit Tests
  As a developer
  I want to be able to use the SentryHelper
  So that each kind of DB connection failure is grouped into one Sentry issue

  Scenario Outline: Testing grouping of DB connection errors
    Given I have a Sentry event with the <kind> "<text>"
    When I group DB connection errors
    Then the event should be grouped as "<group>"

    Examples:
      | kind      | text                                                                                                               | group                  |
      | exception | SQLSTATE[HY000] [1040] Too many connections                                                                        | SQLSTATE[HY000] [1040] |
      | message   | DB connection error (/app/vendor/yiisoft/yii2/db/Connection.php:647): SQLSTATE[HY000] [2002] Connection timed out | SQLSTATE[HY000] [2002] |

  Scenario: Testing that other DB errors keep their default grouping
    Given I have a Sentry event with the exception "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'a' for key 'b'"
    When I group DB connection errors
    Then the event should keep its default grouping
