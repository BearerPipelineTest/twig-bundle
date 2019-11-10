<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\ErrorRenderer;

use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ExistsLoaderInterface;

/**
 * Provides the ability to render custom Twig-based HTML error pages
 * in non-debug mode, otherwise falls back to HtmlErrorRenderer.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class TwigHtmlErrorRenderer implements ErrorRendererInterface
{
    private $twig;
    private $htmlErrorRenderer;
    private $debug;

    public function __construct(Environment $twig, HtmlErrorRenderer $htmlErrorRenderer, bool $debug = false)
    {
        $this->twig = $twig;
        $this->htmlErrorRenderer = $htmlErrorRenderer;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Throwable $exception): FlattenException
    {
        $exception = $this->htmlErrorRenderer->render($exception);

        if ($this->debug || !$template = $this->findTemplate($exception->getStatusCode());
            return $exception;
        }

        return $exception->setAsString($this->twig->render($template, [
            'legacy' => false, // to be removed in 5.0
            'exception' => $exception,
            'status_code' => $exception->getStatusCode(),
            'status_text' => $exception->getStatusText(),
        ]));
    }

    private function findTemplate(int $statusCode): ?string
    {
        $template = sprintf('@Twig/Exception/error%s.html.twig', $statusCode);
        if ($this->templateExists($template)) {
            return $template;
        }

        $template = '@Twig/Exception/error.html.twig';
        if ($this->templateExists($template)) {
            return $template;
        }

        return null;
    }

    /**
     * To be removed in 5.0.
     *
     * Use instead:
     *
     *   $this->twig->getLoader()->exists($template)
     */
    private function templateExists(string $template): bool
    {
        $loader = $this->twig->getLoader();
        if ($loader instanceof ExistsLoaderInterface || method_exists($loader, 'exists')) {
            return $loader->exists($template);
        }

        try {
            $loader->getSourceContext($template);

            return true;
        } catch (LoaderError $e) {
        }

        return false;
    }
}
