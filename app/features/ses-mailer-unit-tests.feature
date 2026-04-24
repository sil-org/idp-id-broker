Feature: SesMailer Unit Tests
  As a developer
  I want to be able to use the SesMailer component
  So that I can send emails via AWS SES

  Scenario: Testing default region
    Given I have a new SesMailer
    When I initialize the SesMailer
    Then the AWS region should be "us-east-1"

  Scenario: Testing custom region
    Given I have a new SesMailer
    When I set the AWS region to "us-west-2"
    And I initialize the SesMailer
    Then the AWS region should be "us-west-2"

  Scenario: Testing email arguments building with CC and BCC
    Given I have a new SesMailer
    And I have a new SesMessage
    When I set the from address to "from@example.com"
    And I set the to address to "to@example.com"
    And I set the cc address to "cc@example.com"
    And I set the bcc address to "bcc@example.com"
    And I set the subject to "Test Subject"
    And I set the text body to "Test Text Body"
    And I set the html body to "<b>Test HTML Body</b>"
    Then the email arguments should be correctly built
    And the email arguments should contain CC address "cc@example.com"
    And the email arguments should contain BCC address "bcc@example.com"

  Scenario: Testing email arguments building without CC and BCC
    Given I have a new SesMailer
    And I have a new SesMessage
    When I set the from address to "from@example.com"
    And I set the to address to "to@example.com"
    And I set the subject to "Test Subject"
    Then the email arguments should be correctly built
    And the email arguments should not contain CC addresses
    And the email arguments should not contain BCC addresses

  Scenario: Testing email arguments building with multiple CC addresses
    Given I have a new SesMailer
    And I have a new SesMessage
    When I set the from address to "from@example.com"
    And I set the to address to "to@example.com"
    And I set the cc address to an array with "cc1@example.com" and "cc2@example.com"
    And I set the subject to "Test Subject"
    Then the email arguments should be correctly built
    And the email arguments should contain CC address "cc1@example.com"
    And the email arguments should contain CC address "cc2@example.com"
