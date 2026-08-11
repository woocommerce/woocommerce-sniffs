<?php
/**
 * Unit test for CommentHooksSniff.
 */

namespace WooCommerce\Tests\Commenting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the CommentHooks sniff.
 */
class CommentHooksUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getErrorList() {
		return array(
			21 => 1, // No comment — MissingHookComment.
			28 => 1, // Single-line comment — HookCommentWrongStyle.
			37 => 1, // Docblock without @since — MissingSinceComment.
			48 => 1, // @since without version — MissingSinceVersionComment.
			65 => 1, // do_action no comment — MissingHookComment.
		);
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList() {
		return array();
	}
}
