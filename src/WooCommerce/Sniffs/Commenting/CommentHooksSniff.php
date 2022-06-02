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
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (! in_array($tokens[$stackPtr]['content'], $this->hooks)) {
            return;
        }

        $previous_comment = $phpcsFile->findPrevious(Tokens::$commentTokens, ( $stackPtr - 1 ));

        if (false !== $previous_comment) {
            $correctly_placed = false;

            if (( $tokens[ $previous_comment ]['line'] + 1 ) === $tokens[ $stackPtr ]['line']) {
                $correctly_placed = true;
            }

            if (true === $correctly_placed) {

                if (\T_COMMENT === $tokens[ $previous_comment ]['code']) {
                    $fix = $phpcsFile->addFixableError(
                        'A "hook" comment must be a "/**" style docblock comment.',
                        $stackPtr,
                        'HookCommentWrongStyle'
                    );

                    if ( $fix ) {
                        $phpcsFile->fixer->beginChangeset();
                        $current_ptr   = $stackPtr;
                        $previous_line = (int) $tokens[ $stackPtr ]['line'] - 1;

                        // Traverse up the pointer stack.
                        while ( true ) {
                            // The pointer has reached the previous line.
                            if ( $tokens[ $stackPtr ]['line'] === $previous_line ) {
                                // If it has an opening PHP tag, we skip to prevent issues.
                                for ( $i = $stackPtr + 1; $i < $current_ptr; $i++ ) {
                                    if ( '<?php' === trim( $tokens[ $i ]['content'] ) ) {
                                        break 2;
                                    }
                                }

                                $description = trim( str_replace( '//', '', $tokens[ $stackPtr ]['content'] ) );

                                // Account for translators comments.
                                if ( preg_match( '/\/\*\s*translators:.+\*\//i', $tokens[ $stackPtr - 1 ]['content'] ) ) {
                                    break;
                                }

                                // Account for /*...*/ style comments.
                                if ( preg_match( '/\/\*.+\*\//', $tokens[ $stackPtr - 1 ]['content'] ) ) {
                                    $description = trim( str_replace( array( '/*', '*/' ), '', $tokens[ $stackPtr - 1 ]['content'] ) );
                                    $phpcsFile->fixer->replaceToken( $stackPtr - 1, '' );
                                } elseif ( preg_match( '/\*\//', $tokens[ $stackPtr - 1 ]['content'] ) ) {
                                    $stack = $stackPtr - 1;

                                    while ( true ) {
                                        // Reached the comment start tag.
                                        if ( preg_match( '/\/\*/', $tokens[ $stack ]['content'] ) ) {
                                            $phpcsFile->fixer->replaceToken( $stack, '/**' . $phpcsFile->eolChar );
                                            break 2;
                                        }

                                        $stack--;
                                    }
                                }

                                if ( '' !== trim( $tokens[ $stackPtr + 1 ]['content'] ) ) {
                                    $padding = '';
                                } else {
                                    $spaces    = strlen( $tokens[ $stackPtr + 1 ]['content'] );
                                    $tabs      = floor( $spaces / 4 );
                                    $remaining = str_repeat( ' ', ( $spaces % 4 ) );
                                    $padding   = str_repeat( "\t", $tabs ) . $remaining;
                                }

                                if ( preg_match( '/\/\/\s*PHPCS:|WPCS:/i', $tokens[ $stackPtr ]['content'] ) ) {
                                    $phpcsFile->fixer->addContent( $stackPtr, $phpcsFile->eolChar . $padding . '/**' . $phpcsFile->eolChar . $padding . ' * Hook' . $phpcsFile->eolChar . $padding . ' *' . $phpcsFile->eolChar . $padding . ' * @since' . $phpcsFile->eolChar . $padding . ' */' . $phpcsFile->eolChar );
                                    break;
                                }

                                $phpcsFile->fixer->replaceToken( $stackPtr, $phpcsFile->eolChar . $padding . '/**' . $phpcsFile->eolChar . $padding . ' * ' . ucfirst( $description ) . $phpcsFile->eolChar . $padding . ' *' . $phpcsFile->eolChar . $padding . ' * @since' . $phpcsFile->eolChar . $padding . ' */' . $phpcsFile->eolChar );
                                break;
                            }

                            $stackPtr--;
                        }
                        $phpcsFile->fixer->endChangeset();
                    }

                    return;
                } elseif (\T_DOC_COMMENT_CLOSE_TAG === $tokens[ $previous_comment ]['code']) {
                    $comment_start = $phpcsFile->findPrevious(\T_DOC_COMMENT_OPEN_TAG, ( $previous_comment - 1 ));

                    // Iterate through each comment to check for "@since" tag.
                    foreach ($tokens[ $comment_start ]['comment_tags'] as $tag) {
                        if ($tokens[$tag]['content'] === '@since') {
                            return;
                        }
                    }

                    $fix = $phpcsFile->addFixableError(
                        'Docblock comment was found for the hook but does not contain a "@since" versioning.',
                        $stackPtr,
                        'MissingSinceComment'
                    );

                    if ( $fix ) {
                        $phpcsFile->fixer->beginChangeset();
                        $comment_end = $phpcsFile->findPrevious( \T_DOC_COMMENT_CLOSE_TAG, $stackPtr );
                        $spaces      = strlen( $tokens[ $comment_end - 1 ]['content'] );
                        $tabs        = floor( $spaces / 4 );
                        $remaining   = str_repeat( ' ', ( $spaces % 4 ) );
                        $padding     = str_repeat( "\t", $tabs ) . $remaining;

                        $phpcsFile->fixer->addContent( $comment_end - 3, $phpcsFile->eolChar . $padding . '* @since' );
                        $phpcsFile->fixer->endChangeset();
                    }

                    return;
                }
            }
        }

        // Found hook but no docblock comment.
        $fix = $phpcsFile->addFixableError(
            'A hook was found, but was not accompanied by a docblock comment on the line above to clarify the meaning of the hook.',
            $stackPtr,
            'MissingHookComment'
        );

        if ( $fix ) {
            $phpcsFile->fixer->beginChangeset();
            $current_ptr   = $stackPtr;
            $previous_line = (int) $tokens[ $stackPtr ]['line'] - 1;

            // Traverse up the pointer stack.
            while ( true ) {
                // The pointer has reached the previous line.
                if ( $tokens[ $stackPtr ]['line'] === $previous_line ) {
                    // If it has an opening PHP tag, we skip to prevent issues.
                    for ( $i = $stackPtr + 1; $i < $current_ptr; $i++ ) {
                        if ( '<?php' === trim( $tokens[ $i ]['content'] ) ) {
                            break 2;
                        }
                    }

                    if ( '' !== trim( $tokens[ $stackPtr + 1 ]['content'] ) ) {
                        $padding = '';
                    } else {
                        $spaces    = strlen( $tokens[ $stackPtr + 1 ]['content'] );
                        $tabs      = floor( $spaces / 4 );
                        $remaining = str_repeat( ' ', ( $spaces % 4 ) );
                        $padding   = str_repeat( "\t", $tabs ) . $remaining;
                    }

                    $phpcsFile->fixer->addContent( $stackPtr, $phpcsFile->eolChar . $padding . '/**' . $phpcsFile->eolChar . $padding . ' * Hook' . $phpcsFile->eolChar . $padding . ' *' . $phpcsFile->eolChar . $padding . ' * @since' . $phpcsFile->eolChar . $padding . ' */' . $phpcsFile->eolChar );
                    break;
                }

                $stackPtr--;
            }
            $phpcsFile->fixer->endChangeset();
        }

        return;
    }
}
