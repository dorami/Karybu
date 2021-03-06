<?php
// florin, 3/19/13, 4:29 PM

namespace Karybu\Module\Debug\EventListener;

use Karybu\Event\DBEvents;
use Karybu\Event\ErrorEvent;
use Karybu\Event\QueryEvent;
use Karybu\EventListener\CustomErrorHandler;
use Karybu\Module\Debug\EventListener\DBQueryInfoListener;
use Karybu\Module\Debug\EventListener\QueryErrorListener;
use Karybu\EventListener\ErrorHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Log\LoggerInterface;


class DebugToolbarListener implements EventSubscriberInterface
{
    const DISABLED = 0;
    const ENABLED = 1;

    protected $mode;
    protected $context;

    /** @var DBQueryInfoListener */
    private $queryInfoListener;
    /** @var QueryErrorListener */
    private $queryErrorListener;
    /** @var ErrorHandler */
    private $errorHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(\ContextInstance $context, $mode = self::ENABLED, LoggerInterface $logger=null)
    {
        $this->mode = (integer) $mode;
        $this->context = $context;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('loadJavascriptFiles'),
            KernelEvents::RESPONSE => array('onKernelResponse', -128),
            KernelEvents::EXCEPTION => array('onKernelException')
        );
    }

    public function loadJavascriptFiles()
    {
        /*$this->context->loadJavascriptPlugin('jquery-ui-1.10');
        $this->context->unloadJavascriptPlugin('ui');*/
        $this->context->addJsFile('modules/debug/tpl/js/main.js');
        $this->context->addCSSFile('modules/debug/tpl/css/main.css');
    }

    public function enableQueriesInfo(DBQueryInfoListener $queryInfoListener)
    {
        $this->queryInfoListener = $queryInfoListener;
    }

    public function enableFailedQueriesInfo(QueryErrorListener $queryErrorListener)
    {
        $this->queryErrorListener = $queryErrorListener;
    }

    public function enablePHPErrorsInfo(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // todo find a better way to mark an exception so that the debug toolbar won't be shown in an exception page
        $event->getRequest()->query->set('no_toolbar', true);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        //TODO deal with redirects here

        if (self::DISABLED === $this->mode
            //|| !$response->headers->has('X-Debug-Token')
            || $response->isRedirection()
            || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
            || 'html' !== $request->getRequestFormat()
            // do not capture modals (or any other request that includes a no_toolbar parameter)
            // also, avoid exceptions
            || $request->query->has('no_toolbar')
            // do not capture redirects or modify XML HTTP Requests
            || $request->isXmlHttpRequest()
        ) {
            return;
        }

        $this->injectToolbar($response);
    }

    public function isEnabled()
    {
        return self::DISABLED !== $this->mode;
    }

    /**
     * Injects the web debug toolbar into the given Response.
     *
     * @param Response $response A Response instance
     */
    protected function injectToolbar(Response $response)
    {
        if (function_exists('mb_stripos')) {
            $posrFunction   = 'mb_strripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction   = 'strripos';
            $substrFunction = 'substr';
        }
        $content = $response->getContent();
        $pos = $posrFunction($content, '</body>');

        if (false !== $pos) {
            $tabs = array();
            if ($this->queryInfoListener) {
                $queries = $this->queryInfoListener->getQueries();
                $this->context->set('queries', $queries);
                $tabs['Queries'] = new \stdClass();
                $tabs['Queries']->content = $this->renderView('queries');
                $tabs['Queries']->badge = '<span class="badge badge-info">' . (($query_count = count($queries)) ? $query_count : '') . '</span>';
            }
            if ($this->queryErrorListener) {
                $queries = $this->queryErrorListener->getFailedQueries();
                $this->context->set('failed_queries', $queries);
                $tabs['Query errors'] = new \stdClass();
                $tabs['Query errors']->content = $this->renderView('failed_queries');
                $tabs['Query errors']->badge = '<span class="badge badge-important">' . (($query_count = count($queries)) ? $query_count : '') . '</span>';
            }
            if ($this->errorHandler) {
                $errors = $this->errorHandler->getErrors();
                $this->context->set('errors', $errors);
                $tabs['PHP Errors'] = new \stdClass();
                $tabs['PHP Errors']->content = $this->renderView('php_errors');
                $tabs['PHP Errors']->badge = '<span class="badge badge-warning">' . (($error_count = count($errors)) ? $error_count : '') . '</span>';
            }
            $this->context->set('data', $tabs);
            if (!isset($_SESSION['debug_state'])){
                $_SESSION['debug_state'] = 'closed';
            }
            $this->context->set('debug_state', $_SESSION['debug_state']);
            if (isset($_SESSION['debug_height'])) {
                $this->context->set('debug_height', $_SESSION['debug_height']);
            }
            if (isset($_SESSION['debug_tab'])) {
                $this->context->set('debug_tab', $_SESSION['debug_tab']);
            }
            $toolbar = $this->renderView('toolbar');
            $content = $substrFunction($content, 0, $pos).$toolbar.$substrFunction($content, $pos);
            $response->setContent($content);
            if ($this->logger) {
                $this->logger->info('Showing debug toolbar');
            }
        }
    }

    private function renderView($template_file) {
        $templateHandler = \TemplateHandler::getInstance();
        return $templateHandler->compile('./modules/debug/tpl', $template_file);
    }

}