<?php

namespace KDKeywords;

use \PDO;
use \SimpleXMLElement;
use \GuzzleHttp\Client;
use \League\CLImate\CLImate;

class AmazonAPI
{
    protected $terminal;
    protected $pdo;
    protected $client;
    protected $AWSAccessKeyId;
    protected $AWSSecretKeyId;
    protected $AssociateTag;


    public function __construct(CLImate $terminal, PDO $pdo, Client $client)
    {
        $this->terminal = $terminal;
        $this->pdo = $pdo;
        $this->client = $client;
        $this->AWSAccessKeyId = getenv('AWS_ACCESSKEY_ID');
        $this->AWSSecretKeyId = getenv('AWS_SECRET_KEY');
        $this->AssociateTag = getenv('AWS_ASSOCIATE_TAG');
    }

    public function search($params = [], $from = null, $to = null)
    {

        $authors = $this->pdo->query("SELECT * FROM authors WHERE id = 1186");

        foreach ($authors as $key => $author) {
            $params['Author'] = $author['name'];
            $authorId = $author['id'];
            $firstRequest = $this->getSignedRequestURL($params);
            try {

                $xml = $this->sendRequest($firstRequest,$authorId,1);

                $contents = new SimpleXMLElement($xml);
                $totalPage = (int) $contents->Items->TotalPages;
                $this->terminal->out("Slip: 0 - Page: 1 - Author: {$params['Author']}");
                $idxPage = 2;
                while($idxPage <= $totalPage){
                    if($idxPage == 11){
                        break; //limit over 10 pages
                    }
                    $slipSec = range(3,5);
                    //wait between 3-5 sec
                    splip($slipSec);

                    $params['itemPage'] = $idxPage;
                    $sequenceRequest = $this->getSignedRequestURL($params);
                    $this->sendRequest($sequenceRequest,$authorId,$idxPage);
                    $this->terminal->out("Slip: {$slipSec} - Page: {$idxPage}/{$totalPage} - Author: {$params['Author']}");

                    $idxPage++;
                }



                die;


            } catch (\Exception $e) {
                $this->terminal->White()->backgroundRed($e->getMessage());
                exit();
            }


        }


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
        $request_url = [];
        $request_url['base_url'] = 'http://' . $endpoint . $uri;
        $request_url['query'] = $canonical_query_string . '&Signature=' . rawurlencode($signature);

        return $request_url;
    }

    private function sendRequest($request,$authorId,$page){

        $response = $this->client->request(
            'GET', $request['base_url'],
            ['query' => $request['query']]
        );

        $xml = $response->getBody()->getContents();

        $stm = $this->pdo->prepare("INSERT INTO data_feeds (request_url, request_query,response,author_id,page_number) 
                    VALUES(?,?,?,$authorId, $page)");

        $values = [$request['base_url'],$request['query'], $xml];

        $stm->execute($values);

        return $xml;
    }

}