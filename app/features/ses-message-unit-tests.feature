Feature: SesMessage Unit Tests
  As a developer
  I want to be able to use the SesMessage component
  So that I can send emails via AWS SES

  Scenario: Testing charset
    Given I have a new SesMessage
    When I set the charset to "UTF-16"
    Then the charset should be "UTF-16"

  Scenario: Testing From address with a string
    Given I have a new SesMessage
    When I set the from address to "tester@example.com"
    Then the from address should be "tester@example.com"

  Scenario: Testing From address with an array (email as key)
    Given I have a new SesMessage
    When I set the from address to an array with "tester@example.com" as key and "Tester" as value
    Then the from address should be "Tester <tester@example.com>"

  Scenario: Testing To address
    Given I have a new SesMessage
    When I set the to address to "recipient@example.com"
    Then the to address should contain "recipient@example.com"

  Scenario: Testing multiple To addresses
    Given I have a new SesMessage
    When I set the to address to an array with "r1@example.com" and "r2@example.com"
    Then the to address should contain "r1@example.com"
    And the to address should contain "r2@example.com"

  Scenario: Testing To address with names
    Given I have a new SesMessage
    When I set the to address to the following:
      | email | name |
      | r1@example.com | Recipient One |
    Then the to address should contain "Recipient One <r1@example.com>"

  Scenario: Testing To address with multiple names
    Given I have a new SesMessage
    When I set the to address to the following:
      | email | name |
      | r1@example.com | Recipient One |
      | r2@example.com | Recipient Two |
    Then the to address should contain "Recipient One <r1@example.com>"
    And the to address should contain "Recipient Two <r2@example.com>"

  Scenario: Testing Reply-To address
    Given I have a new SesMessage
    When I set the reply-to address to "reply@example.com"
    Then the reply-to address should contain "reply@example.com"

  Scenario: Testing Reply-To default (should be From address)
    Given I have a new SesMessage
    And I set the from address to "tester@example.com"
    Then the reply-to address should be "tester@example.com"

  Scenario: Testing CC address
    Given I have a new SesMessage
    When I set the cc address to "cc@example.com"
    Then the cc address should contain "cc@example.com"

  Scenario: Testing BCC address
    Given I have a new SesMessage
    When I set the bcc address to "bcc@example.com"
    Then the bcc address should contain "bcc@example.com"

  Scenario: Testing Subject
    Given I have a new SesMessage
    When I set the subject to "Test Subject"
    Then the subject should be "Test Subject"

  Scenario: Testing Text Body
    Given I have a new SesMessage
    When I set the text body to "Hello World"
    Then the text body should be "Hello World"

  Scenario: Testing HTML Body
    Given I have a new SesMessage
    When I set the html body to "<b>Hello World</b>"
    Then the html body should be "<b>Hello World</b>"

  Scenario: Testing Text Body generation from HTML Body
    Given I have a new SesMessage
    When I set the html body to "<b>Hello World</b>"
    Then the text body should be "Hello World"

  Scenario: Testing HTML Body generation from Text Body
    Given I have a new SesMessage
    When I set the text body to "Hello < World"
    Then the html body should be "Hello &lt; World"

  Scenario: Testing toString
    Given I have a new SesMessage
    When I set the text body to "Hello World"
    Then toString should return "Hello World"

  Scenario: Testing not supported attach
    Given I have a new SesMessage
    When I try to attach a file
    Then it should throw a NotSupportedException

  Scenario: Testing not supported attachContent
    Given I have a new SesMessage
    When I try to attach content
    Then it should throw a NotSupportedException

  Scenario: Testing not supported embed
    Given I have a new SesMessage
    When I try to embed a file
    Then it should throw a NotSupportedException

  Scenario: Testing not supported embedContent
    Given I have a new SesMessage
    When I try to embed content
    Then it should throw a NotSupportedException
