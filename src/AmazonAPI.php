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

        $condition = "";
        if(!empty($from)){
            $condition .= " AND authors.id >= {$from} ";
            if(!empty($to)){
                $condition .= " AND authors.id <= {$to} ";
            }
        }

        $authors = $this->pdo->query("SELECT authors.* FROM authors 
             LEFT JOIN data_feeds ON authors.id = data_feeds.author_id 
             WHERE data_feeds.author_id IS NULL 
             {$condition}  
             ORDER BY authors.books_number ASC");
        $tot = count($authors);
        $idx = 1;
        foreach ($authors as $key => $author) {
            $params['Author'] = $author['name'];
            $authorId = $author['id'];
            $params['ItemPage'] = 1;
            $firstRequest = $this->getSignedRequestURL($params);
            try {

                $contents = $this->sendRequest($firstRequest,$authorId,1);
                if(empty($contents)){
                    $this->terminal->White()->backgroundRed("{$idx}/{$tot} ERROR - Sleep: 0 sec - Page: 1/undefined - Author: ({$authorId}) {$params['Author']}");
                    $idx++;
                    continue;
                }

                $totalPage =  (int) $contents->Items->TotalPages;
                $this->terminal->out("{$idx}/{$tot} Sleep: 0 sec - Page: 1/{$totalPage} - Author: ({$authorId}) {$params['Author']}");
                $sleepSec = rand(5,10);
                sleep($sleepSec);
                $idxPage = 2;
                while($idxPage <= $totalPage){
                    if($idxPage == 11){
                        break; //limit over 10 pages
                    }
                    if($idxPage>2){
                        $sleepSec = rand(10,15);
                        sleep($sleepSec);
                    }


                    $params['ItemPage'] = $idxPage;
                    $sequenceRequest = $this->getSignedRequestURL($params);
                    $this->sendRequest($sequenceRequest,$authorId,$idxPage);

                    if(empty($contents)){
                        $this->terminal->White()->backgroundRed("{$idx}/{$tot} ERROR - Sleep: {$sleepSec} sec - Page: {$idxPage}/{$totalPage} - Author: ({$authorId}) {$params['Author']}");
                    }else{
                        $this->terminal->out("{$idx}/{$tot} Sleep: {$sleepSec} sec - Page: {$idxPage}/{$totalPage} - Author: ({$authorId}) {$params['Author']}");
                    }

                    $idxPage++;
                }


            } catch (\Exception $e) {
                $this->terminal->White()->backgroundRed($e->getMessage());
                exit();
            }
            $idx++;

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
        $success = 1;

        $response = $this->client->request(
            'GET', $request['base_url'],
            ['query' => $request['query']]
        );

        $xml = $response->getBody()->getContents();
        $contents = new SimpleXMLElement($xml);
        if($contents->Items->Request->IsValid == 'False'){
            $success = 0;
        }
        $stm = $this->pdo->prepare("INSERT INTO data_feeds (request_url, request_query,response,author_id,page_number,is_success) 
                    VALUES(?,?,?,$authorId, $page,$success)");

        $values = [$request['base_url'],$request['query'], $xml];

        $stm->execute($values);
        if($success){
            return $contents;
        }else{
            return null;
        }

    }

}