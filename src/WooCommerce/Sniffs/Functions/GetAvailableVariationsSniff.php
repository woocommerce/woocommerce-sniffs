<?php
/**
 * Sniff to discourage expensive usage of WC_Product_Variable::get_available_variations().
 */

namespace WooCommerce\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Flags calls to get_available_variations() that use the expensive 'array' return type.
 *
 * The default 'array' return type processes expensive data per variation: HTML generation,
 * price calculations, image lookups, and formatting. For products with many variations,
 * this creates significant overhead. The 'objects' return type is much lighter when
 * full processed variation data isn't needed.
 */
class GetAvailableVariationsSniff implements Sniff
{
    /**
     * The method name to check.
     *
     * @var string
     */
    const METHOD_NAME = 'get_available_variations';

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
     * Processes this test when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check if this is our target method name.
        if (self::METHOD_NAME !== $tokens[$stackPtr]['content']) {
            return;
        }

        // Ensure this is a method call (preceded by -> or ::).
        $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if (false === $prevToken) {
            return;
        }

        if (T_OBJECT_OPERATOR !== $tokens[$prevToken]['code']
            && T_DOUBLE_COLON !== $tokens[$prevToken]['code']
        ) {
            return;
        }

        // Find the opening parenthesis.
        $openParen = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if (false === $openParen || T_OPEN_PARENTHESIS !== $tokens[$openParen]['code']) {
            return;
        }

        // Find what's inside the parentheses.
        $closeParen = $tokens[$openParen]['parenthesis_closer'];
        $firstArg = $phpcsFile->findNext(T_WHITESPACE, $openParen + 1, $closeParen, true);

        // Case 1: No arguments - using default 'array'.
        if (false === $firstArg) {
            $phpcsFile->addWarning(
                'get_available_variations() defaults to the expensive \'array\' return type. '
                . 'Consider using \'objects\' if you only need WC_Product_Variation objects.',
                $stackPtr,
                'DefaultArrayReturn'
            );
            return;
        }

        // Case 2: Check if the argument is explicitly 'array'.
        if (T_CONSTANT_ENCAPSED_STRING === $tokens[$firstArg]['code']) {
            $argValue = trim($tokens[$firstArg]['content'], '\'"');
            if ('array' === $argValue) {
                $phpcsFile->addWarning(
                    'get_available_variations(\'array\') is expensive for products with many variations. '
                    . 'Consider using \'objects\' if you only need WC_Product_Variation objects.',
                    $stackPtr,
                    'ExplicitArrayReturn'
                );
            }
        }
    }
}
