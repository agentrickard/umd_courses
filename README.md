# UMD Courses Module

A Drupal 11 module that fetches and displays course data from the University of Maryland API (https://api.umd.io/v1/courses).

## Features

- Fetches course data from the UMD API using a dedicated service
- Displays courses on a dedicated `/courses` page
- Implements caching to improve performance (1-hour cache)
- Responsive design with CSS Grid layout
- Error handling and logging
- Clean, accessible course card layout

## Installation

1. Copy the `umd_courses` directory to your Drupal modules directory
2. Enable the module through the Drupal admin interface or via Drush:
   ```bash
   drush en umd_courses
   ```
3. Visit `/courses` to view the course listings

## API Integration

The module uses the UMD API endpoint: `https://api.umd.io/v1/courses`

### Data Structure

Each course includes:
- Course ID and name
- Department information
- Credits and semester
- Course description
- Grading methods
- General education requirements
- Available sections

### Caching

- Course data is cached for 1 hour to reduce API calls
- Cache keys are prefixed with `umd_courses:`
- Cache can be cleared through Drupal's cache management

## Architecture

### Service Layer
- `UmdApiClient` - Handles all API communication
- Dependency injection for HTTP client, cache, and logger
- Error handling with fallback to empty arrays

### Controller
- `CoursesController` - Manages the `/courses` page
- Uses the API service to fetch and display data
- Implements proper Drupal 11 controller patterns

### Theming
- Custom Twig template: `umd-courses-page.html.twig`
- CSS styling with UMD brand colors
- JavaScript enhancements for interactivity

## Customization

### Styling
Edit `css/umd-courses.css` to customize the appearance.

### Course Limit
The default limit is 100 courses. Modify in `CoursesController::coursesPage()`.

### Cache Duration
Modify `UmdApiClient::CACHE_EXPIRE` constant to change cache duration.

## Requirements

- Drupal ^10 || ^11
- PHP 8.1+
- GuzzleHttp (included with Drupal core)

## API Documentation

For more information about the UMD API, visit: https://api.umd.io/

## Support

This module is designed for educational and demonstration purposes. For production use, consider additional error handling, rate limiting, and performance optimizations.
