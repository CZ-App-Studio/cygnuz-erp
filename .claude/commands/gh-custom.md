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
- If UI is affected, use Playwright MCP to test the live application directly

**Step 5: Run all tests and verify implementation**
- `php artisan test` to verify all tests pass
- Use Playwright MCP to verify UI functionality works correctly in live application
- **STOP HERE** - Inform user that implementation and testing is complete
- Wait for user confirmation before proceeding to commit and PR creation

**Step 6: After user confirmation - Clean up before commit**
- Remove any unnecessary test files, factories, or seeders created during development
- Remove any issue-specific test cases that are not needed for the core fix
- Remove the entire issue-specific test file `tests/Feature/Issues/Issue$ARGUMENTSTest.php` if it was created
- Keep only essential test coverage that validates the general functionality

**Step 7: After user confirmation - Format and commit**
- Run `./vendor/bin/pint` to format code
- Commit changes with proper message
- Push to GitHub with `git push -u origin fix/issue-$ARGUMENTS`

**Step 8: After user confirmation - Create pull request using gh CLI**
- First, get the issue details to extract the creator's username
- Use `gh pr create` to create pull request with the issue creator as reviewer:
  - `gh pr create --title "fix: Issue title (issue #$ARGUMENTS)" --body "PR description" --reviewer <issue_creator_username>`
- **IMPORTANT**: Automatically add the issue creator as a reviewer using `--reviewer` flag
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
