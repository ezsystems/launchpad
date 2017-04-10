Feature: Test command

    Scenario: Running test command
        When I run "test" command
        Then I should see "Test2"
        Then I should see:
            | Test1 |
            | Test3 |

