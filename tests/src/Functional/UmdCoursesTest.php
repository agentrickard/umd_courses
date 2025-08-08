<?php

namespace Drupal\Tests\umd_courses\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Functional tests for UMD Courses module.
 *
 * @group umd_courses
 */
class UmdCoursesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['umd_courses'];

  /**
   * Test the courses page loads successfully.
   */
  public function testCoursesPageLoads() {
    // Create a user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Visit the courses page.
    $this->drupalGet('/courses');

    // Check that the page loads without errors.
    $this->assertSession()->statusCodeEquals(200);

    // Check for the page title.
    $this->assertSession()->titleEquals('UMD Courses | Drupal');

    // Check for the main heading.
    $this->assertSession()->pageTextContains('University of Maryland Courses');
  }

  /**
   * Test the courses page displays courses.
   */
  public function testCoursesPageDisplaysCourses() {
    // Create a user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Visit the courses page.
    $this->drupalGet('/courses');

    // Check that the courses list container is present.
    $this->assertSession()->elementExists('css', '.umd-courses-page');

    // Check that either courses are displayed or no courses message is shown.
    $courses_list = $this->getSession()->getPage()->find('css', '.courses-list');
    $no_courses = $this->getSession()->getPage()->find('css', '.no-courses');

    $this->assertTrue(
      ($courses_list !== NULL) || ($no_courses !== NULL),
      'Either courses list or no courses message should be present'
    );

    // If courses are displayed, check the structure.
    if ($courses_list !== NULL) {
      // Check for course cards.
      $course_items = $this->getSession()->getPage()->findAll('css', '.course-item');

      if (!empty($course_items)) {
        // Verify course card structure.
        $first_course = $course_items[0];

        $this->assertNotNull(
          $first_course->find('css', '.course-title'),
          'Course title should be present'
        );

        $this->assertNotNull(
          $first_course->find('css', '.course-id-badge'),
          'Course ID badge should be present'
        );

        $this->assertNotNull(
          $first_course->find('css', '.course-divider'),
          'Course divider should be present'
        );
      }
    }
  }

  /**
   * Test the courses page HTML structure.
   */
  public function testCoursesPageHtmlStructure() {
    // Create a user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/courses');

    // Check for header structure.
    $this->assertSession()->elementExists('css', '.courses-header');
    $this->assertSession()->elementExists('css', '.header-content');

    // Check for proper heading hierarchy.
    $this->assertSession()->elementExists('css', 'h2');

    // Check that CSS is loaded.
    $this->assertSession()->responseContains('umd-courses.css');
  }

  /**
   * Test the courses page is accessible with proper permissions.
   */
  public function testCoursesPageAccessible() {
    // Test as authenticated user with access content permission.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('/courses');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test the courses page route.
   */
  public function testCoursesRoute() {
    // Create a user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $url = Url::fromRoute('umd_courses.courses_page');
    $this->assertEquals('/courses', $url->toString());

    // Verify the route works.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test the courses page meta tags and SEO elements.
   */
  public function testCoursesPageMetaTags() {
    // Create a user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/courses');

    // Check page title is set correctly.
    $this->assertSession()->titleEquals('UMD Courses | Drupal');

    // Check that our specific H2 is present and has correct text.
    $this->assertSession()->elementExists('css', '.courses-header h2');
    $page = $this->getSession()->getPage();
    $our_h2 = $page->find('css', '.courses-header h2');
    $this->assertNotNull($our_h2, 'Our courses H2 should be present');
    $this->assertEquals(
      'University of Maryland Courses',
      $our_h2->getText()
    );
  }

  /**
   * Test courses page responsive design elements.
   */
  public function testCoursesPageResponsiveElements() {
    // Create a user with permission to access content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/courses');

    // Check that responsive classes and structure exist.
    $this->assertSession()->elementExists('css', '.courses-list');

    // Verify that course items have proper structure for responsive design.
    $course_items = $this->getSession()->getPage()->findAll('css', '.course-item');

    if (!empty($course_items)) {
      foreach ($course_items as $item) {
        $this->assertNotNull(
          $item->find('css', '.course-header'),
          'Each course should have a header section'
        );
      }
    }
  }

}
