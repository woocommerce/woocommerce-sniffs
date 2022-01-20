<?php
/**
 * Sniff to ensure hooks have doc comments.
 */

namespace WooCommerce\Sniffs\Commenting;

use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Comment tags sniff.
 */
class CommentHooksSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
    ];

    /**
     * A list of specfic hooks to listen to.
     *
     * @var array
     */
    public $hooks = [
        'do_action',
        'apply_filters',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     */
    public function process(File $phpcsFile, $stack_ptr)
    {
        $tokens = $phpcsFile->getTokens();

        if (! in_array($tokens[ $stack_ptr ]['content'], $this->hooks)) {
            return;
        }

        $previous_comment = $phpcsFile->findPrevious( Tokens::$commentTokens, ( $stack_ptr - 1 ) );

        if ( false !== $previous_comment ) {
            if ( ( $tokens[ $previous_comment ]['line'] + 1 ) === $tokens[ $stack_ptr ]['line'] ) {
                return;
            } else {
                $next_non_whitespace = $phpcsFile->findNext( \T_WHITESPACE, ( $previous_comment + 1 ), $stack_ptr, true );

                if ( false === $next_non_whitespace || $tokens[ $next_non_whitespace ]['line'] === $tokens[ $stack_ptr ]['line'] ) {
                    // No non-whitespace found or next non-whitespace is on same line as hook call.
                    return;
                }
                unset( $next_non_whitespace );
            }
        }

        // Found hooks but no doc comment.
        $phpcsFile->addWarning(
            sprintf( 'Documentation/comment needed for hook to explain what the hook does: %s', $tokens[ $stack_ptr ]['content'] ),
            $stack_ptr,
            'MissingHooksComment'
        );
        return;
    }
}
