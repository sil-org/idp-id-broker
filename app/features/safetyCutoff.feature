
Feature: Preventing too many changes at once to protect against iffy data.

  Scenario Outline: Preventing too many user-deactivations in a full sync
    Given <activeInBroker> users are active in the ID Broker
      And running a full sync would deactivate <numToDeactivate> users
      And the safety cutoff is <safetyCutoff>
    When I sync all the users from the ID Store to the ID Broker
    Then an exception <exceptionOrNot> have been thrown

    Examples:
      | activeInBroker | numToDeactivate | safetyCutoff | exceptionOrNot |
      |      100       |      0          |     0.15     |  should NOT    |
      |      100       |     15          |     0.15     |  should NOT    |
      |      100       |     16          |     0.15     |  SHOULD        |
      |      100       |    100          |     0.15     |  SHOULD        |
      |      100       |     59          |     0.60     |  should NOT    |
      |      100       |     60          |     0.60     |  should NOT    |
      |      100       |     61          |     0.60     |  SHOULD        |
      |      100       |     62          |     0.60     |  SHOULD        |
      |        2       |      0          |    -1        |  SHOULD        |
      |        2       |      0          |     0        |  should NOT    |
      |        2       |      0          |     1        |  should NOT    |
      |        2       |      0          |     0.99     |  should NOT    |
      |        2       |      0          |     1.00     |  should NOT    |
      |        2       |      0          |     1.01     |  should NOT    |
      |        2       |      0          |     abcd     |  SHOULD        |

  Scenario Outline: Preventing too many user-creations in a full sync
    Given <activeInBroker> users are active in the ID Broker
      And running a full sync would create <numToCreate> users
      And the safety cutoff is <safetyCutoff>
    When I sync all the users from the ID Store to the ID Broker
    Then an exception <exceptionOrNot> have been thrown

    Examples:
      | activeInBroker | numToCreate | safetyCutoff | exceptionOrNot |
      |      100       |      0      |     0.15     |  should NOT    |
      |      100       |     15      |     0.15     |  should NOT    |
      |      100       |     16      |     0.15     |  SHOULD        |
      |      100       |    100      |     0.15     |  SHOULD        |


  Scenario Outline: Preventing too many combined changes in an incremental sync
    Given <activeInBroker> users are active in the ID Broker
      And an incremental sync would add <add>, update <update>, and deactivate <deact> users
      And the safety cutoff is <safetyCutoff>
    When I run an incremental sync
    Then an exception <exceptionOrNot> have been thrown

    Examples:
      | activeInBroker | add | update | deact | safetyCutoff | exceptionOrNot |
      |      100       |   0 |     0  |    0  |     0.15     |  should NOT    |
      |      100       |  15 |     0  |    0  |     0.15     |  should NOT    |
      |      100       |  16 |     0  |    0  |     0.15     |  SHOULD        |
      |      100       |   0 |    15  |    0  |     0.15     |  should NOT    |
      |      100       |   0 |    16  |    0  |     0.15     |  SHOULD        |
      |      100       |   0 |     0  |   15  |     0.15     |  should NOT    |
      |      100       |   0 |     0  |   16  |     0.15     |  SHOULD        |
      |      100       |   5 |     5  |    5  |     0.15     |  should NOT    |
      |      100       |   6 |     5  |    5  |     0.15     |  SHOULD        |
      |      100       |   5 |     6  |    5  |     0.15     |  SHOULD        |
      |      100       |   5 |     5  |    6  |     0.15     |  SHOULD        |

