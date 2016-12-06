<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Tooling\ScanForErrorMiddleware;

class NextInvocationScanner
{
    const TEMPLATE = <<< 'EOT'
<?php
%s
EOT;

    /**
     * @var array
     */
    private $tokens;

    /**
     * @param string $code Code to tokenize and scan.
     */
    public function __construct($code)
    {
        $this->tokens = token_get_all(sprintf(
            self::TEMPLATE,
            $code
        ));
    }

    /**
     * Scan for invocations of $next() with error arguments.
     */
    public function scan()
    {
        $next                 = false;
        $inNext               = false;
        $parenDepth           = 0;
        $commaCount           = 0;
        $errorInvocationFound = false;

        foreach ($this->tokens as $token) {
            if (! $next && $this->isNext($token)) {
                // Hit a $next variable
                $next = true;
                continue;
            }

            if ($next && ! $inNext && is_array($token)) {
                // $next followed by token
                $next = false;
                continue;
            }

            if ($next && ! $inNext && $token === '(') {
                // $next followed by opening paren (invocation)
                $inNext = true;
                $parenDepth = 1;
                continue;
            }

            if (! $inNext) {
                // Not in a $next invocation; nothing to examine
                continue;
            }

            if (is_array($token)) {
                // Hit a token; continue to next
                continue;
            }

            if ($token === '(') {
                // Hit an opening paren; increase paren depth
                $parenDepth += 1;
                continue;
            }

            if ($token === ')') {
                // Hit an closing paren; decrease paren depth
                $parenDepth -= 1;
                if ($parenDepth === 0) {
                    // If we hit parenDepth 0, we finished invocation;
                    // reset state until we hit the next invocation.
                    $next       = false;
                    $inNext     = false;
                    $commaCount = 0;
                }
                continue;
            }

            if ($token === ',' && $parenDepth === 1) {
                // Found a comma separating a $next argument
                $commaCount += 1;
                if ($commaCount > 1) {
                    // More than two arguments! Error middleware invocation!
                    $errorInvocationFound = true;
                    break;
                }
                continue;
            }
        }

        return $errorInvocationFound;
    }

    /**
     * Does the token represent $next?
     *
     * @param string|array $token
     * @return bool
     */
    public function isNext($token)
    {
        return is_array($token)
            && $token[0] === T_VARIABLE
            && $token[1] === '$next';
    }
}
