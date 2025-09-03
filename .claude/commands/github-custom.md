# .claudecode/commands.yaml
version: 1
commands:
  # Command to work on a GitHub issue
  work-on-issue:
    description: "Start working on a GitHub issue"
    prompt: |
      I need to work on GitHub issue #{{issue_number}}.
      1. First, fetch the issue details using GitHub MCP
      2. Create a new branch: fix/issue-{{issue_number}}
      3. Analyze the issue and suggest a solution
      4. Implement the fix
      5. Run tests to verify
      6. Create a commit with message: "fix: #{{issue_number}} - {{issue_title}}"
    parameters:
      issue_number:
        type: string
        description: "GitHub issue number"
        required: true

  # Command to verify fix with tests
  verify-fix:
    description: "Verify issue fix with automated tests"
    prompt: |
      Verify that issue #{{issue_number}} has been fixed:
      1. Run PHPUnit tests: php artisan test
      2. Run Playwright E2E tests for affected features
      3. Check if the specific issue scenario passes
      4. Generate a test report
      5. Update the GitHub issue with test results
    parameters:
      issue_number:
        type: string
        description: "Issue number to verify"
        required: true

  # Full workflow command
  fix-and-verify:
    description: "Complete issue fix workflow"
    prompt: |
      Complete workflow for GitHub issue #{{issue_number}}:
      
      Step 1: Fetch issue details from GitHub
      - Use GitHub MCP to get issue description, labels, and context
      
      Step 2: Create feature branch
      - Branch name: fix/issue-{{issue_number}}
      
      Step 3: Implement the fix
      - Analyze the codebase
      - Implement solution
      - Follow Laravel best practices
      
      Step 4: Write/Update tests
      - Create PHPUnit test for the fix
      - Create Playwright E2E test if UI is affected
      
      Step 5: Run all tests
      - php artisan test
      - npm run test:e2e
      
      Step 6: If tests pass
      - Commit changes
      - Push to GitHub
      - Create pull request referencing issue #{{issue_number}}
      - Comment on issue with PR link
      
      Use GitHub MCP for all GitHub operations and Playwright MCP for E2E testing.
    parameters:
      issue_number:
        type: string
        required: true

  # Command to create test for issue
  create-issue-test:
    description: "Create automated test for GitHub issue"
    prompt: |
      Create comprehensive tests for issue #{{issue_number}}:
      1. Fetch issue details using GitHub MCP
      2. Create PHPUnit test in tests/Feature/Issues/Issue{{issue_number}}Test.php
      3. Create Playwright E2E test in tests/e2e/issues/issue-{{issue_number}}.spec.js
      4. Tests should reproduce the issue scenario
      5. Run tests to confirm they fail (reproducing the issue)
      6. Return test file paths
    parameters:
      issue_number:
        type: string
        required: true

  # Command to check issue status
  check-issue-status:
    description: "Check and update issue status"
    prompt: |
      For GitHub issue #{{issue_number}}:
      1. Get current issue status from GitHub
      2. Run related tests
      3. Check if referenced in any commits
      4. Check if PR exists
      5. Provide comprehensive status report
      6. Suggest next steps
    parameters:
      issue_number:
        type: string
        required: true
