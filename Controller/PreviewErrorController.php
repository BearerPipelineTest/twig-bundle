<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\Controller;

use Symfony\Component\ErrorRenderer\ErrorRenderer;
use Symfony\Component\ErrorRenderer\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * PreviewErrorController can be used to test error pages.
 *
 * It will create a test exception and forward it to another controller.
 *
 * @author Matthias Pigulla <mp@webfactory.de>
 */
class PreviewErrorController
{
    protected $kernel;
    protected $controller;
    private $errorRenderer;

    public function __construct(HttpKernelInterface $kernel, $controller, ErrorRenderer $errorRenderer = null)
    {
        $this->kernel = $kernel;
        $this->controller = $controller;
        $this->errorRenderer = $errorRenderer;
    }

    public function previewErrorPageAction(Request $request, $code)
    {
        $exception = FlattenException::createFromThrowable(new \Exception('Something has intentionally gone wrong.'), $code, ['X-Debug' => false]);

        if (null === $this->controller && null !== $this->errorRenderer) {
            return new Response($this->errorRenderer->render($exception, $request->getPreferredFormat()), $code);
        }

        /*
         * This Request mimics the parameters set by
         * \Symfony\Component\HttpKernel\EventListener\ExceptionListener::duplicateRequest, with
         * the additional "showException" flag.
         */

        $subRequest = $request->duplicate(null, null, [
            '_controller' => $this->controller,
            'exception' => $exception,
            'logger' => null,
            'format' => $request->getRequestFormat(),
            'showException' => false,
        ]);

        return $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
