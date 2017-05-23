<?php

namespace KDKeywords;

use \PDO;
use \GuzzleHttp\Client;

class AmazonAPI
{

    protected $pdo;
    protected $client;
    protected $AWSAccessKeyId;
    protected $AWSSecretKeyId;
    protected $AssociateTag;

    public function __construct(PDO $pdo, Client $client)
    {
        $this->pdo = $pdo;
        $this->client = $client;
        $this->AWSAccessKeyId = getenv('AWS_ACCESSKEY_ID');
        $this->AWSSecretKeyId = getenv('AWS_SECRET_KEY');
        $this->AssociateTag = getenv('AWS_ASSOCIATE_TAG');
    }

    public function search($params = [], $from = null, $to = null)
    {
        $request_url = $this->getSignedRequestURL($params);


        
    }

    private function getSignedRequestURL($params)
    {
        // The region you are interested in (default amazon.com)
        $endpoint = "webservices.amazon.com";
        if (!empty($params['EndPoint'])) {
            $endpoint = $params['EndPoint'];
            unset($params['EndPoint']);
        }

        $uri = "/onca/xml";

        $params['Operation'] = 'ItemSearch';
        $params['Service'] = 'AWSECommerceService';
        $params['AWSAccessKeyId'] = $this->AWSAccessKeyId;
        $params['AssociateTag'] = $this->AssociateTag;

        // Set current timestamp if not set
        if (!isset($params["Timestamp"])) {
            $params["Timestamp"] = gmdate('Y-m-d\TH:i:s\Z');
        }
        // Sort the parameters by key
        ksort($params);

        $pairs = array();

        foreach ($params as $key => $value) {
            array_push($pairs, rawurlencode($key) . "=" . rawurlencode($value));
        }

        // Generate the canonical query
        $canonical_query_string = join("&", $pairs);

        // Generate the string to be signed
        $string_to_sign = "GET\n" . $endpoint . "\n" . $uri . "\n" . $canonical_query_string;

        // Generate the signature required by the Product Advertising API
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->AWSSecretKeyId, true));

        // Generate the signed URL
        $request_url = 'http://' . $endpoint . $uri . '?' . $canonical_query_string . '&Signature=' . rawurlencode($signature);

        return $request_url;
    }

}