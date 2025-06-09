# Library Management System - Testing Guide

This guide explains how to set up and run the test suite for the Library Management System.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

## Setting Up Test Environment

1. Install dependencies:
```bash
composer install
```

2. Set up test database:
```bash
composer run-script setup-test-db
```

This script will:
- Create a test database named `library_test`
- Create all necessary tables
- Set up required constraints and indexes

## Running Tests

### Run all tests
```bash
composer test
```

### Run specific test suite
```bash
./vendor/bin/phpunit --testsuite Unit
```

### Run specific test file
```bash
./vendor/bin/phpunit tests/Unit/LoginTest.php
```

## Test Coverage

Generate test coverage report:
```bash
composer run-script test-coverage
```

The coverage report will be available in:
- HTML format: `tests/coverage/index.html`
- Text format: `tests/coverage.txt`

## Test Documentation

Test execution results are documented in:
- HTML format: `tests/testdox.html`
- Text format: `tests/testdox.txt`

## Test Structure

```
tests/
├── Unit/                     # Unit tests
│   ├── LoginTest.php        # Login functionality tests
│   ├── ChangePasswordTest.php   # Password change tests
│   ├── AdminCategoryTest.php    # Category management tests
│   ├── AdminAuthorTest.php      # Author management tests
│   ├── AdminBookTest.php        # Book management tests
│   ├── AdminIssueBookTest.php   # Book issuing tests
│   └── StudentIssuedBooksTest.php   # Student book history tests
├── Feature/                  # Feature tests (if needed)
├── setup_test_db.php        # Database setup script
└── README.md                # This file
```

## Test Cases Overview

### Login Tests (F001)
- Successful login with valid credentials
- Failed login with invalid credentials
- Failed login with empty fields

### Change Password Tests (F003)
- Successful password change
- Failed change with wrong current password
- Failed change with mismatched new passwords

### Category Management Tests (F008)
- Update category name and status
- Test empty category name
- Test category name length limits
- Delete category

### Author Management Tests (F011, F012)
- Update author name
- Test empty author name
- Test author name length limits
- Delete author
- Test duplicate author names

### Book Management Tests (F014, F015)
- Update book details
- Test book name length limits
- Test empty required fields
- Test invalid ISBN/price formats
- Delete book
- Test book relationships with categories and authors

### Book Issue Tests (F016, F017)
- Issue book to student
- Test invalid book/student IDs
- Update issued book details
- Test multiple book issues
- Test book return process

### Student Book History Tests (F018)
- View issued books history
- Test empty history
- View currently issued books
- View returned books

## Database Schema

The test database includes the following tables:
- `admin`: Administrator accounts
- `tblstudents`: Student information
- `tblcategory`: Book categories
- `tblauthors`: Book authors
- `tblbooks`: Book information
- `tblissuedbookdetails`: Book lending records

## Best Practices

1. Always run tests in isolation
2. Use the test database, never the production database
3. Clean up test data after each test
4. Write meaningful test names that describe the scenario
5. Follow the Arrange-Act-Assert pattern in test methods

## Troubleshooting

If you encounter issues:

1. Verify database connection settings in `phpunit.xml`
2. Ensure test database exists and is accessible
3. Check PHP version compatibility
4. Verify all dependencies are installed
5. Make sure you have proper permissions for database operations

## Contributing

When adding new tests:

1. Follow the existing test structure
2. Use meaningful test method names
3. Add proper documentation
4. Ensure all tests are isolated
5. Update this README if needed