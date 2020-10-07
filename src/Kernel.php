<?php

namespace Copper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Loader\PhpFileLoader;

use Symfony\Component\Config\FileLocator;

class Kernel
{
    const CONFIG_FOLDER = 'config';
    const ROUTES_CONFIG_FILE = 'routes.php';

    /** @var RouteCollection */
    protected $routes;

    public function __construct()
    {
        $this->configureRoutes();
    }
    
    /**
     * Returns Base Uri
     
     * @param bool $relative
     * @return string
     */
    public static function getBaseUri($relative = false)
    {
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        $hostName = $_SERVER['HTTP_HOST'];
        $protocol = $_SERVER['REQUEST_SCHEME'];

        $path = $pathInfo['dirname'];

        if ($relative)
            return $path;
        else
            return $protocol . '://' . $hostName . $path;
    }

    /**
     * Returns client IP address
     *
     * @return string
     */
    public static function getIPAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Returns path to project root directory
     *
     * @return string
     */
    public static function getProjectPath()
    {
        return dirname($_SERVER['SCRIPT_FILENAME']) . '/..';
    }

    /**
     * Returns path to package root directory
     *
     * @return string
     */
    public static function getPackagePath()
    {
        return dirname(__DIR__);
    }

    /**
     * Handles Request
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $matcher = new UrlMatcher($this->routes, $requestContext);

        try {
            $this->configureMatchedRequestAttributes($matcher, $request);
            $response = $this->getRequestControllerResponse($request, $requestContext);
        } catch (\Exception $e) {
            if ($e instanceof MethodNotAllowedException) {
                $response = $this->errorResponse('Templating method [' . $request->getMethod() . '] is not allowed.');
            } else {
                $response = $this->errorResponse($e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Default Response for error
     *
     * @param string $message The response message
     * @param int $status The response status code
     *
     * @return Response
     */
    protected function errorResponse($message, $status = 404)
    {
        return new Response('<b>Error</b>: ' . $message, $status);
    }

    /**
     * Configure route parameters
     *
     * @param UrlMatcher $matcher
     * @param Request $request
     */
    protected function configureMatchedRequestAttributes(UrlMatcher $matcher, Request $request)
    {
        $routeDefinitionKeys = ['_controller', '_route'];

        $matchCollection = $matcher->match($request->getPathInfo());

        $routeDefinitionParams = array_intersect_key($matchCollection, array_flip($routeDefinitionKeys));
        $controllerParams = ['_route_params' => array_diff_key($matchCollection, $routeDefinitionParams)];

        $request->attributes->add(array_merge($routeDefinitionParams, $controllerParams));
    }

    /**
     * Returns Response from provided controller in routes configuration file
     *
     * @param Request $request
     * @param RequestContext $requestContext
     *
     * @return Response
     */
    protected function getRequestControllerResponse(Request $request, RequestContext $requestContext)
    {
        // controller as function
        $controller = $request->attributes->get('_controller');

        // controller as class. (e.g [DefaultController::class, 'index']) OR '\App\Controller\DefaultController::index')
        if (is_array($controller) || (is_string($controller) && strpos($controller, '::') !== false)) {

            if (is_string($controller)) {
                $controller = explode('::', $controller);
            }

            // pass Templating and RequestContext initialized class to controller
            $instance = new $controller[0]($request, $requestContext, $this->routes);

            $controller = [$instance, $controller[1]];
        }

        if (!is_callable($controller)) {
            $response = $this->errorResponse('Controller is not callable', Response::HTTP_BAD_REQUEST);
        } else {
            $response = call_user_func_array($controller, $request->attributes->get('_route_params'));
        }

        return $response;
    }

    /**
     *  Configure default and application routes from {APP|CORE}/config/routes.php
     */
    protected function configureRoutes()
    {
        // Load default routes
        $path = $this::getPackagePath() . '/' . $this::CONFIG_FOLDER;
        $loader = new PhpFileLoader(new FileLocator($path));
        $this->routes = $loader->load($this::ROUTES_CONFIG_FILE);

        // Load application routes
        $path = $this::getProjectPath() . '/' . $this::CONFIG_FOLDER;
        if (file_exists($path . '/' . $this::ROUTES_CONFIG_FILE)) {
            $loader = new PhpFileLoader(new FileLocator($path));
            $this->routes->addCollection($loader->load($this::ROUTES_CONFIG_FILE));
        }
    }
}
