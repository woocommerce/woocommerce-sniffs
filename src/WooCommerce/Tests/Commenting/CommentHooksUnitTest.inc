<?php
/**
 * Test fixture for CommentHooksSniff.
 */

// =============================================
// Test 1: Hook with proper docblock and @since — no error.
// =============================================

/**
 * Filter something.
 *
 * @since 1.0.0
 */
apply_filters( 'wc_test_proper_docblock', $value );

// =============================================
// Test 2: Hook with no comment — MissingHookComment error.
// =============================================

apply_filters( 'wc_test_no_comment', $value );

// =============================================
// Test 3: Hook with single-line comment — HookCommentWrongStyle error.
// =============================================

// This is a single-line comment.
apply_filters( 'wc_test_single_line_comment', $value );

// =============================================
// Test 4: Hook with docblock but no @since — MissingSinceComment error.
// =============================================

/**
 * Filter something without a since tag.
 */
apply_filters( 'wc_test_no_since', $value );

// =============================================
// Test 5: Hook with @since but no version — MissingSinceVersionComment error.
// =============================================

/**
 * Filter something with empty since.
 *
 * @since
 */
apply_filters( 'wc_test_since_no_version', $value );

// =============================================
// Test 6: do_action with proper docblock — no error.
// =============================================

/**
 * Action hook.
 *
 * @since 2.0.0
 */
do_action( 'wc_test_proper_action', $arg );

// =============================================
// Test 7: do_action with no comment — MissingHookComment error.
// =============================================

do_action( 'wc_test_action_no_comment', $arg );

// =============================================
// Test 8: Cross-reference "This filter is documented in" — no error (PR #47).
// =============================================

/** This filter is documented in includes/some-file.php */
apply_filters( 'wc_test_crossref_filter', $value );

// =============================================
// Test 9: Cross-reference "This action is documented in" — no error (PR #47).
// =============================================

/** This action is documented in includes/some-file.php */
do_action( 'wc_test_crossref_action', $arg );

