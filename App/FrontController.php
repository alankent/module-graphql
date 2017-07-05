<?php
namespace AlanKent\GraphQL\App;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\AreaList;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;
use AlanKent\GraphQL\App\Context;

//use \GraphQL\Examples\Blog\Types;
//use \GraphQL\Examples\Blog\AppContext;
//use \GraphQL\Examples\Blog\Data\DataSource;
use \GraphQL\Schema;
use \GraphQL\GraphQL;
use \GraphQL\Type\Definition\Config;
use \GraphQL\Error\FormattedError;

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

    /** @var Context */
    private $context;

    /** @var QueryTypeFactory */
    private $queryTypeFactory;

    /**
     * FrontController constructor.
     * @param ResultFactory $resultFactory
     * @param AreaList $areaList
     * @param ScopeInterface $configScope
     * @param Context $context
     * @param QueryTypeFactory $context
     */
    public function __construct(
        ResultFactory $resultFactory,
        AreaList $areaList,
        ScopeInterface $configScope,
        Context $context,
        QueryTypeFactory $queryTypeFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->areaFrontName = $areaList->getFrontName($configScope->getCurrentScope());
        $this->context = $context;
        $this->queryTypeFactory = $queryTypeFactory;
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

            // Parse incoming query and variables
            if ($req->isPost()) {
                $contentType = $req->getHeader('Content-Type', '');
                if (strpos($contentType, 'application/json') !== false) { // TODO: strpos??
                    $payload = $req->getContent();
                    $data = json_decode($payload, true);
                    $query = isset($data['query']) ? $data['query'] : '';
                    $variables = isset($data['variables']) ? $data['variables'] : [];
                } else {
                    $query = $req->getParam('query');
                    $variables = json_decode($req->getParam('variables'), true);
                }
            } else /*if ($req->isGet())*/ {
                $query = $req->getParam('query');
                $variables = json_decode($req->getParam('variables'), true);

            //} else {
                //throw new \Exception("Expected POST or GET request.", 404);
            }

            // Process the request.
            $graphqlResponse = $this->graphql($query, $variables);

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
     * @param String $contentType The request content type.
     * @return array Associative array to encode as JSON.
     */
    private function graphql($query, $variables)
    {
        try {
            // Initialize our fake data source
            //DataSource::init();

            // Prepare context that will be available in all field resolvers (as 3rd argument):
            //$appContext = new AppContext();
            //$appContext->viewer = DataSource::findUser('1'); // simulated "currently logged-in user"
            //$appContext->rootUrl = 'http://localhost:8080';
            //$appContext->request = $_REQUEST;

            // GraphQL schema to be passed to query executor:
            $schema = new Schema([
                'query' => $this->queryTypeFactory->create()
            ]);
            $result = GraphQL::execute(
                $schema,
                $query,
                null,
                $this->context,
                (array) $variables
            );
        } catch (\Exception $error) {
            $httpStatus = 500;
            $result['errors'] = [FormattedError::create('Unexpected Error')];
        }

        return $result;
    }
}

