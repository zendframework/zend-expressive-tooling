<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Tooling\CreateHandler;

trait NormalizeTemplateIdentifierTrait
{
    /**
     * Normalizes identifier to lowercase, dash-separated words.
     */
    private function normalizeTemplateIdentifier(string $identifier) : string
    {
        $pattern     = ['#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#', '#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'];
        $replacement = ['-\1', '-\1'];
        $identifier  = preg_replace($pattern, $replacement, $identifier);
        return strtolower($identifier);
    }
}
