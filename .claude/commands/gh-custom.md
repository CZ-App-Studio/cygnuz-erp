Complete workflow for GitHub issue $ARGUMENTS:

**Step 1: Fetch issue details from GitHub**
- Use GitHub MCP to get issue description, labels, and context

**Step 2: Create feature branch**  
- Branch name: fix/issue-$ARGUMENTS

**Step 3: Implement the fix**
- Analyze the codebase
- Implement solution following Laravel best practices

**Step 4: Write/Update tests (NO DATABASE WIPING)**
- Create PHPUnit test for the fix in `tests/Feature/Issues/Issue$ARGUMENTSTest.php`
- **IMPORTANT**: Do NOT use `RefreshDatabase` trait - it wipes all data
- Create non-database tests that validate:
  - Code logic and response structures
  - File contents and patterns
  - Service class behavior
- Create Playwright E2E test if UI is affected

**Step 5: Run all tests**
- `php artisan test` to verify all tests pass
- `npm run test:e2e` if E2E tests exist

**Step 6: Clean up before commit**
- Remove any unnecessary test files, factories, or seeders created during development
- Remove any issue-specific test cases that are not needed for the core fix
- Keep only essential test coverage

**Step 7: Format and commit**
- Run `./vendor/bin/pint` to format code
- Commit changes with proper message
- Push to GitHub with `git push -u origin fix/issue-$ARGUMENTS`

**Step 8: Create pull request using gh CLI**
- Use `gh pr create` to create pull request referencing issue #$ARGUMENTS
- Include comprehensive description with:
  - Summary linking to the issue
  - Technical details of the fix
  - Test plan verification
  - Impact assessment

**Testing Guidelines:**
- Tests must validate the fix without touching the database
- Use file content validation, service mocking, or response structure testing
- Avoid `use RefreshDatabase;` or any database-wiping traits
- Focus on testing the actual issue scenario and prevention
