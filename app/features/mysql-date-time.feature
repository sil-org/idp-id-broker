
Feature: MysqlDateTime

  Scenario: Check that a recent date is recognized as recent
    Given I say that recent is in the last 3 days
    When I ask if 3 days ago is recent
    Then I see that that date is recent

  Scenario: Check that today is recognized as recent
    Given I say that recent is in the last 3 days
    When I ask if 0 days ago is recent
    Then I see that that date is recent

  Scenario: Check that a barely old date is recognized as NOT recent
    Given I say that recent is in the last 3 days
    When I ask if 4 days ago is recent
    Then I see that that date is NOT recent

  Scenario: Check that an old date is recognized as NOT recent
    Given I say that recent is in the last 3 days
    When I ask if 24 days ago is recent
    Then I see that that date is NOT recent

  Scenario Outline: Check interval parsing
    When I parse interval "<interval>"
    Then "<result>" interval is returned

    Examples:
      | interval  | result              |
      | +1 s      | + INTERVAL 1 SECOND |
      | -1 Sec    | - INTERVAL 1 SECOND |
      | +2 Secs   | <null>              |
      | +1 Second | + INTERVAL 1 SECOND |
      | 2 Seconds | + INTERVAL 2 SECOND |
      | +1 m      | + INTERVAL 1 MINUTE |
      | -1 Min    | - INTERVAL 1 MINUTE |
      | +2 mins   | <null>              |
      | +1 minute | + INTERVAL 1 MINUTE |
      | 2 Minutes | + INTERVAL 2 MINUTE |
      | +1 h      | + INTERVAL 1 HOUR   |
      | -1 hr     | <null>              |
      | +1 hour   | + INTERVAL 1 HOUR   |
      | 2 Hours   | + INTERVAL 2 HOUR   |
      | +1 d      | + INTERVAL 1 DAY    |
      | -1 Day    | - INTERVAL 1 DAY    |
      | 2 days    | + INTERVAL 2 DAY    |
      | +1 w      | + INTERVAL 1 WEEK   |
      | -1 Week   | - INTERVAL 1 WEEK   |
      | 2 weeks   | + INTERVAL 2 WEEK   |
      | +1 mo     | <null>              |
      | +1 month  | + INTERVAL 1 MONTH  |
      | -1 month  | - INTERVAL 1 MONTH  |
      | 2 Months  | + INTERVAL 2 MONTH  |
      | +1 y      | + INTERVAL 1 YEAR   |
      | -1 Yr     | - INTERVAL 1 YEAR   |
      | +2 yrs    | <null>              |
      | +1 year   | + INTERVAL 1 YEAR   |
      | 2 Years   | + INTERVAL 2 YEAR   |

  Scenario Outline: Check invert interval
    When I invert interval "<interval>"
    Then "<result>" interval is returned

    Examples:
      | interval          | result            |
      | + INTERVAL 1 YEAR | - INTERVAL 1 YEAR |
      | - INTERVAL 5 DAY  | + INTERVAL 5 DAY  |
      | - incorrect text  | <null>            |
