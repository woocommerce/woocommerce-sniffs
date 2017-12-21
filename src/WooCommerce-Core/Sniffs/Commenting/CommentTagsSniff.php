<?php
/**
 * Sniff to prohibit some file comment tags on WooCommerce
 */

namespace WooCommerce\Sniffs\Core\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Comment tags sniff.
 */
class CommentTagsSniff implements Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    /**
     * List of prohibited tags.
     *
     * @return array
     */
    protected function getProhibitedTags(): array
    {
        return [
            '@author'    => 'AuthorTag',
            '@category'  => 'CategoryTag',
            '@license'   => 'LicenseTag',
            '@copyright' => 'CopyrightTag',
        ];
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

        foreach ($this->getProhibitedTags() as $key => $value) {
            if ($key === $tokens[$stackPtr]['content']) {
                $error = "{$key} tags are prohibited";
                $phpcsFile->addError($error, $stackPtr, $value);
            }
        }
    }
}
