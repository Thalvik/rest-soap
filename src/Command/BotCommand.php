<?php

namespace App\Command;

use SoapClient;
use SoapFault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BotCommand extends Command
{
    protected static $defaultName = 'app:run-bot';
    private static $bearerToken = null;
    private static $apiUsername;
    private static $apiPassword;
    private static $baseUri;
    private static $soapUri;
    private static $customerMail;
    private static $customerPassword ;
    private static $customerFirstName;
    private static $customerLastName;
    private static $customerStreet;
    private static $customerCountry;
    private static $orderAmount;
    private static $shippingAmount;
    private static $taxAmount;

    private $router;
    private $container;

    public function __construct(RouterInterface $router, ContainerInterface $container)
    {
        parent::__construct();
        $this->router = $router;
        $this->container = $container;
        $this->setDummyData();
    }

    /**
     * Sets data from parameters
     */
    private function setDummyData()
    {
        self::$apiUsername = $this->container->getParameter('sp.apiusername');
        self::$apiPassword = $this->container->getParameter('sp.apipassword');
        self::$baseUri = $this->container->getParameter('sp.baseuri');
        self::$soapUri = $this->container->getParameter('sp.soapuri');
        self::$customerMail = $this->container->getParameter('sp.customeremail');
        self::$customerPassword = $this->container->getParameter('sp.customerpassword');
        self::$customerFirstName = $this->container->getParameter('sp.customerfirstname');
        self::$customerLastName = $this->container->getParameter('sp.customerlastname');
        self::$customerStreet = $this->container->getParameter('sp.customerstreet');
        self::$customerCountry = $this->container->getParameter('sp.customercountry');
        self::$orderAmount = $this->container->getParameter('sp.orderamount');
        self::$shippingAmount = $this->container->getParameter('sp.shippingamount');
        self::$taxAmount = $this->container->getParameter('sp.taxamount');
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->setDescription('Runs bot')
        ->setHelp('Runs bot to make all API requests and show response');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Create client
        $client = $this->httpRequest(
            $this->router->generate('app_oauth_client_authentication'),
            'POST',
            [
                'redirect-uri' => self::$baseUri,
                'grant-type' => 'password'
            ],
            200
        );

        if (empty($client)) {
            die('Client not created');
        }

        $clientId = array_key_exists('client_id', $client) ? $client['client_id'] : '';
        $clientSecret = array_key_exists('client_secret', $client) ? $client['client_secret'] : '';

        //Get token
        if ($clientId != '' && $clientSecret != '') {
            $token = $this->httpRequest(
                $this->router->generate('fos_oauth_server_token'),
                'POST',
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'password',
                    'username' => self::$apiUsername,
                    'password' => self::$apiPassword
                ],
                200
            );

            if (empty($token)) {
                die('Token not created');
            }

            self::$bearerToken = array_key_exists('access_token', $token) ? $token['access_token'] : '';

            //We will not implement to save token for now, out of this scope
        }

        //Read customer
        $customer = $this->httpRequest(
            $this->router->generate('app_rest_user_get'),
            'GET',
            [
                'email' => self::$customerMail
            ],
            200
        );

        //Create customer if does not exist
        if (empty($customer)) {
            $customer = $this->httpRequest(
                $this->router->generate('app_rest_user_add'),
                'POST',
                [
                    'email' => self::$customerMail,
                    'plainPassword' => self::$customerPassword
                ],
                201
            );
        }

        //Update customer
        if (!empty($customer)) {
            $customer = $this->httpRequest(
                $this->router->generate('app_rest_user_update'),
                'PUT',
                [
                    'id' => $customer['id'],
                    'firstName' => self::$customerFirstName,
                    'lastName' => self::$customerLastName,
                    'street' => self::$customerStreet,
                    'country' => self::$customerCountry,
                ],
                201
            );
        }


        //Create customer order
        $customerOrder = $this->httpRequest(
            $this->router->generate('app_rest_userorder_add'),
            'POST',
            [
                'userId' => $customer['id'],
                'orderAmount' => self::$orderAmount,
                'shippingAmount' => self::$shippingAmount
            ],
            201
        );

        //Read customer order
        $customerOrder = $this->httpRequest(
            $this->router->generate('app_rest_userorder_get'),
            'GET',
            [
                'id' => $customerOrder['id']
            ],
            200
        );

        //Update customer order
        if (!empty($customerOrder)) {
            $customerOrder = $this->httpRequest(
                $this->router->generate('app_rest_userorder_update'),
                'PUT',
                [
                    'id' => $customerOrder['id'],
                    'orderAmount' => self::$orderAmount,
                    'shippingAmount' => self::$shippingAmount,
                    'taxAmount' => self::$taxAmount,
                ],
                201
            );
        }


        //Read all customers and their order with SOAP request
        try {
            $client = new SoapClient(self::$baseUri . self::$soapUri, array(
                'cache_wsdl' => 0,
                'trace' => true
            ));
            $response = $client->soap();
            print_r($response);
        } catch (SoapFault $e) {
            print_r($e->getMessage());
        }

        //Delete order
        $this->httpRequest(
            $this->router->generate('app_rest_userorder_delete'),
            'DELETE',
            [
                'id' => $customerOrder['id'],
            ],
            200
        );

        //Delete customer
        $this->httpRequest(
            $this->router->generate('app_rest_user_delete'),
            'DELETE',
            [
                'id' => $customer['id'],
            ],
            200
        );
    }

    /**
     * @param string $route
     * @param array $params
     * @param string $method
     * @param int $statusCode
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function httpRequest(string $route, string $method, array $params = [], int $statusCode = 200)
    {
        //Init the HTTP client
        $httpClient = HttpClient::create([
            'auth_bearer' => self::$bearerToken,
        ]);

        //Form url
        $url = self::$baseUri . $route;

        try {
            //Log action
            error_log('Fetching route ' . $url . ' with method ' . $method . ' and params: '
                . implode(', ', $params));
            if ($method === 'GET') {
                $options = [
                    'query' => $params,
                ];
            } else {
                $options = [
                    'json' => $params,
                ];
            }

            //Make HTTP request
            $response = $httpClient->request($method, $url, $options);
            if ($response->getStatusCode(false) == $statusCode) {
                return $response->toArray();
            } else {
                throw new ClientException($response);
            }
        } catch (ClientExceptionInterface $e) {
            print_r($e->getResponse()->toArray(false));
        } catch (DecodingExceptionInterface $e) {
            print_r($e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            print_r($e->getMessage());
        } catch (ServerExceptionInterface $e) {
            print_r($e->getMessage());
        } catch (TransportExceptionInterface $e) {
            print_r($e->getMessage());
        }

        return [];
    }
}
