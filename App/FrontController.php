<?php
namespace AlanKent\GraphQL\App;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\AreaList;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;

/**
 * Front controller for the 'graphql' area. Converts web service requests
 * (HTTP POST of GraphQL queries) into appropriate PHP function calls
 * then returns the JSON response.
 */
class FrontController implements FrontControllerInterface
{
    /** @var ResultFactory */
    private $resultFactory;

    /** @var string */
    private $areaFrontName;

    /**
     * FrontController constructor.
     * @param ResultFactory $resultFactory
     * @param AreaList $areaList
     * @param ScopeInterface $configScope
     */
    public function __construct(
        ResultFactory $resultFactory,
        AreaList $areaList,
        ScopeInterface $configScope
    ) {
        $this->resultFactory = $resultFactory;
        $this->areaFrontName = $areaList->getFrontName($configScope->getCurrentScope());
    }

    /**
     * Process a HTTP request holding an Alexa request.
     * @param RequestInterface $request The HTTP request, including POST data.
     * @return ResultInterface The formed response.
     */
    public function dispatch(RequestInterface $request)
    {
        // RequestInterface does not have all the methods yet. This gives better type hinting.
        /** @var HttpRequest $req */
        $req = $request;

        // Only respond to '/graphql'
        $graphqlPath = '/' . $this->areaFrontName;

        try {

            // Check URL for a match.
            if ($req->getPathInfo() !== $graphqlPath) {
                throw new \Exception("Unsupported URL path: instead of {$req->getPathInfo()} use $graphqlPath.", 404);
            }

            if (!$req->isPost()) {
                throw new \Exception("Only POST requests containing GraphQL requests are supported.");
            }

            // Decode JSON request.
            $graphqlRequest = $req->getContent();

            // Process the request.
            $graphqlResponse = $this->graphql($graphqlRequest);

            // Serialize the result.
            /** @var \Magento\Framework\Controller\Result\Json $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $result->setHttpResponseCode(200);
            $result->setHeader('Content-Type', 'application/json', true);
            $result->setData($graphqlResponse);
            return $result;

        } catch (\Exception $e) {
            /** @var \Magento\Framework\Controller\Result\Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHttpResponseCode($e->getCode() >= 200 ? $e->getCode() : 500);
            $result->setHeader('Content-Type', 'text/plain', true);
            $result->setContents($e->getMessage());
            return $result;
        }
    }

    /**
     * Process a GraphQL request.
     * @param String $request The GraphQL to parse and process.
     * @return array Associative array to encode as JSON.
     */
    private function graphql($request)
    {
	//TODO!
        $response = array();
        $response['version'] = "1.0";

        return $response;
    }
}
