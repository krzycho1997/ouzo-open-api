<?php

namespace Ouzo\OpenApi;

use Ouzo\Http\HttpStatus;
use Ouzo\Injection\Annotation\Inject;
use Ouzo\OpenApi\Extractor\RequestBodyExtractor;
use Ouzo\OpenApi\Extractor\ResponseExtractor;
use Ouzo\OpenApi\Extractor\UriParametersExtractor;
use Ouzo\Routing\RouteRule;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Strings;
use ReflectionClass;

class InternalPathFactory
{
    #[Inject]
    public function __construct(
        private HiddenChecker $hiddenChecker,
        private UriParametersExtractor $uriParametersExtractor,
        private RequestBodyExtractor $requestBodyExtractor,
        private ResponseExtractor $responseExtractor,
        private OperationIdGenerator $operationIdGenerator
    )
    {
    }

    public function create(RouteRule $routeRule): ?InternalPath
    {
        if ($this->hiddenChecker->isHidden($routeRule)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($routeRule->getController());
        $reflectionMethod = $reflectionClass->getMethod($routeRule->getAction());
        $reflectionParameters = $reflectionMethod->getParameters();

        $httpMethod = $routeRule->getMethod();
        $responseCode = Arrays::getValue($routeRule->getOptions(), 'code', HttpStatus::OK);

        $details = $this->createInternalPathDetails($routeRule, $reflectionClass);
        $parameters = $this->uriParametersExtractor->extract($details->getUri(), $httpMethod, $reflectionParameters);
        $requestBody = $this->requestBodyExtractor->extract($reflectionParameters, $httpMethod);
        $response = $this->responseExtractor->extract($responseCode, $reflectionMethod);

        return new InternalPath($details, $parameters, $requestBody, $response);
    }

    private function createInternalPathDetails(RouteRule $routeRule, ReflectionClass $reflectionClass): InternalPathDetails
    {
        $uri = $this->sanitizeUri($routeRule);
        $tag = Strings::camelCaseToUnderscore($reflectionClass->getShortName());
        $action = Strings::camelCaseToUnderscore($routeRule->getAction());
        $summary = "{$this->removeUnderscore($tag)} {$this->removeUnderscore($action)}";
        $operationId = $this->operationIdGenerator->generateForRouteRule($routeRule);
        $method = strtolower($routeRule->getMethod());

        return new InternalPathDetails($uri, $tag, $summary, $operationId, $method);
    }

    private function sanitizeUri(RouteRule $routeRule): string
    {
        $uri = $routeRule->getUri();
        $uri = preg_replace('/:(.*?)\//', '{\1}/', $uri);
        return preg_replace('/:(.*?)$/', '{\1}', $uri);
    }

    private function removeUnderscore(?string $string): string
    {
        return str_replace('_', ' ', $string);
    }
}
