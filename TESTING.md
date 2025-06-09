# Online Library Management System - Testing Guide

This document provides instructions for setting up and running tests for the Online Library Management System.

## Quick Start

The easiest way to run tests is using the provided test runner script:

```bash
php run_tests.php
```

This script will:
1. Check your environment for required dependencies
2. Set up the test database
3. Run all tests
4. Display test results

To generate a coverage report, add the `--coverage` flag:

```bash
php run_tests.php --coverage
```

## Manual Setup and Testing

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

### Step 1: Install Dependencies

```bash
composer install
```

### Step 2: Set Up Test Database

```bash
composer run-script setup-test-db
```

Or manually:

```bash
php tests/setup_test_db.php
```

### Step 3: Run Tests

Run all tests:
```bash
composer test
```

Or:
```bash
./vendor/bin/phpunit
```

Run a specific test suite:
```bash
./vendor/bin/phpunit --testsuite Unit
```

Run a specific test file:
```bash
./vendor/bin/phpunit tests/Unit/LoginTest.php
```

## Test Structure

The test suite is organized as follows:

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
├── Feature/                  # Feature tests
├── Support/                  # Test support files
├── setup_test_db.php        # Database setup script
└── README.md                # Detailed testing documentation
```

## Test Coverage

To generate a test coverage report, you need to have either Xdebug or PCOV extension installed:

### Installing Xdebug (recommended)

#### For Windows:
1. Download the appropriate Xdebug DLL from https://xdebug.org/download
2. Add to your php.ini:
   ```
   [xdebug]
   zend_extension=xdebug
   xdebug.mode=coverage
   ```
3. Restart your web server

#### For Linux/macOS:
```bash
pecl install xdebug
```
Then add the configuration to php.ini as shown above.

### Generating Coverage Report

Once you have a coverage driver installed:

```bash
composer run-script test-coverage
```

Or:

```bash
./vendor/bin/phpunit --coverage-html tests/coverage
```

Then open `tests/coverage/index.html` in your browser to view the report.

If you see a "No code coverage driver available" warning, it means you need to install Xdebug or PCOV.

## Test Documentation

PHPUnit generates documentation of test execution in two formats:

```bash
./vendor/bin/phpunit --testdox-html tests/testdox.html
./vendor/bin/phpunit --testdox-text tests/testdox.txt
```

These reports are automatically generated when running tests through the standard test command.

## Tested Features

The test suite covers the following features:

1. **Authentication (F001, F003)**
   - Admin/Student login
   - Password change functionality

2. **Category Management (F008)**
   - Update category name and status
   - Category validation
   - Category deletion

3. **Author Management (F011, F012)**
   - Update author information
   - Author validation
   - Author deletion

4. **Book Management (F014, F015)**
   - Update book details
   - Book validation
   - Book deletion

5. **Book Issuing (F016, F017)**
   - Issue books to students
   - Update issued book details
   - Return book processing

6. **Student Features (F018)**
   - View issued books history

## Configuration

Test configuration is defined in `phpunit.xml`. You can modify this file to:
- Change database connection settings
- Add or remove test suites
- Configure code coverage settings
- Set environment variables

The default configuration uses:
- Database name: `library_test`
- Username: `root`
- Password: `` (empty)
- Host: `localhost`
- Port: `3306`

## Best Practices

When writing or modifying tests:
1. Always run tests in isolation
2. Use the test database, never the production database
3. Clean up test data after each test
4. Write meaningful test names that describe the scenario
5. Follow the Arrange-Act-Assert pattern in test methods

## Troubleshooting

If you encounter issues:

1. **Database Connection Errors**
   - Check MySQL credentials in `phpunit.xml`
   - Ensure MySQL service is running
   - Verify the test database exists

2. **Missing Dependencies**
   - Run `composer install` to install required packages

3. **Permission Issues**
   - Ensure proper file permissions for test directories

4. **PHP Version Compatibility**
   - Verify you're using PHP 7.4 or higher
   - Check with `php -v`

5. **Test Database Setup Issues**
   - Run `php tests/setup_test_db.php` manually to see detailed errors

For more detailed information, see the comprehensive documentation in `tests/README.md`.