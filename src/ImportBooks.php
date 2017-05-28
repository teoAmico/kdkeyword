<?php

namespace KDKeywords;
use \PDO;
use \SimpleXMLElement;
use \League\CLImate\CLImate;

class ImportBooks
{
    protected $terminal;
    protected $pdo;

    public function __construct(CLImate $terminal, PDO $pdo)
    {
        $this->terminal = $terminal;
        $this->pdo = $pdo;
    }

    public function run(){
        $dataFeedRows = $this->pdo->query("SELECT MAX(id) AS maxId FROM data_feeds");
        $idx = 1;
        $maxFeed = $dataFeedRows->fetch( PDO::FETCH_ASSOC );
        while($idx <= $maxFeed['maxId']){
            $feedObj = $this->pdo->query("SELECT * FROM data_feeds WHERE id = '{$idx}' AND is_success = 1");
            $feed = $feedObj->fetch( PDO::FETCH_ASSOC );
            if(empty($feed)){
                $idx++;
                continue;
            }

            //parse xml response
            $xmlObj = new SimpleXMLElement($feed['response']);

            $items = $xmlObj->Items->Item;
            $totalPage = (int) $xmlObj->Items->TotalPages;
            $itemPagePosition = 1;

            foreach ($items as $key => $item){

                $bookValue = [
                    'total_page' => $totalPage,
                    'author_id' => $feed['author_id'],
                    'feed_id'=> $feed['id'],
                    'item_page'=> $feed['page_number'],
                    'item_page_position' => $itemPagePosition,

                ];

                $itemPagePosition++;

                $bookValue['asin'] = $item->ASIN;

                $book = $this->pdo->query("SELECT id FROM books WHERE asin = '{$bookValue['asin']}'");
                if(!empty($book)){
                    //book exist in db continue
                    continue;
                }

                $bookValue['sales_rank'] = $item->SalesRank;
                $bookValue['author'] = $item->ItemAttributes->Author;
                $bookValue['binding'] = $item->ItemAttributes->Binding;
                $bookValue['format'] = $item->ItemAttributes->Format;
                $bookValue['is_adult_product'] = $item->ItemAttributes->IsAdultProduct;
                $bookValue['language_name'] = $item->ItemAttributes->Languages->Language->Name;
                $bookValue['language_type'] = $item->ItemAttributes->Languages->Language->Type;
                $bookValue['number_of_pages'] = $item->ItemAttributes->NumberOfPages;
                $bookValue['product_group'] = $item->ItemAttributes->ProductGroup;
                $bookValue['product_type_name']= $item->ItemAttributes->ProductTypeName;
                $bookValue['publication_date'] = $item->ItemAttributes->PublicationDate;
                $bookValue['release_date'] = $item->ItemAttributes->ReleaseDate;
                $bookValue['title'] = $item->ItemAttributes->Title;
                $bookValue['detail_page_url'] = $item->ItemAttributes->DetailPageURL;


                //store books
                $bookId = null;

                //description editorial_reviews separate table
                $descriptions = $item->EditorialReviews->EditorialReview;
                foreach ($descriptions as $key =>$desc){
                    $descriptionValues = [];
                    $descriptionValues['book_id'] = $bookId;
                    $descriptionValues['content'] = $desc->EditorialReview->Content;
                    $descriptionValues['source'] = $desc->EditorialReview->Source;
                    $descriptionValues['is_link_suppressed'] = $desc->EditorialReview->IsLinkSuppressed;

                    $this->pdo->query("");
                }

                //similar_products table
                $similarProducts = $item->SimilarProducts;
                foreach ($similarProducts as $key =>$similar){
                    $similarValues = [];
                    $similarValues['asin'] = $similar->SimilarProduct->ASIN;
                    $similarValues['title'] = $similar->SimilarProduct->Title;
                    $similarProducts['book_id'] = $bookId;


                    $this->pdo->query("");
                }

            }
            die;//temp
            $idx++;
        }


    }


}