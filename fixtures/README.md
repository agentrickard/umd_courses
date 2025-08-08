# UMD Courses Module - Fixtures

This directory contains mock data fixtures for testing and development purposes.

## Purpose

The `courses_api_response.json` file simulates the response from a single API call to:
`https://api.umd.io/v1/courses`

This allows developers to test the UMD Courses module without relying on the live API, which offers several benefits:

- **Offline development** - Work on the module without internet access
- **Consistent data** - Test with a predictable set of courses
- **Edge cases** - Easily add or modify data to test different scenarios
- **Faster tests** - Avoid network latency during testing

## Data Structure

The JSON file contains an array of 30 course objects, each with fields that match the UMD API response structure:

- `course_id`
- `semester`
- `name`
- `dept_id`
- `department`
- `credits`
- `description`
- `grading_method`
- `gen_ed`
- `core`
- `relationships` (coreqs, prereqs, etc.)
- `sections`

## Usage

To use this mock data in a browser testing context:

1. **Modify the `UmdApiClient`** to include a mock mode
2. **Load the JSON data** from this file in mock mode
3. **Return the mock data** instead of making a live API call

### Example (in `UmdApiClient.php`):
```php
public function getCourses($limit = 30) {
  // Check for a configuration setting to enable mock mode.
  if ($this->config->get('mock_mode_enabled')) {
    $json_data = file_get_contents(
      DRUPAL_ROOT . '/' . drupal_get_path('module', 'umd_courses') . '/fixtures/courses_api_response.json'
    );
    return json_decode($json_data, TRUE);
  }
  
  // ... existing API call logic ...
}
```

## Data Freshness

The data in this file was captured on **August 8, 2025**.

To update the fixture:

1. Make an API call to `https://api.umd.io/v1/courses?per_page=30`
2. Save the response body to `courses_api_response.json`

## Scenarios

This fixture file covers a range of courses from different departments:

- African American and Africana Studies (AAAS)
- Computer Science (CMSC)
- English (ENGL)
- History (HIST)
- Mathematics (MATH)
- Psychology (PSYC)
- Biology (BIOL)

It also includes courses with:

- Multiple sections
- Gen Ed and Core requirements
- Prerequisites and corequisites
- Cross-listed courses
- Varying credit counts and grading methods

## Customization

Feel free to modify this file to test different scenarios:

- **Empty description** - Test how cards render without a description
- **Long department names** - Check for text overflow issues
- **Missing fields** - Ensure the module handles incomplete data gracefully
- **Large number of sections** - Verify the UI can handle many sections
