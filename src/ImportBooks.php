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

    public function run()
    {
        try {

            $dataFeedRows = $this->pdo->query("SELECT MAX(id) AS maxId FROM data_feeds");
            $idx = 27;
            $maxFeed = $dataFeedRows->fetch(PDO::FETCH_ASSOC);
            while ($idx <= $maxFeed['maxId']) {

                $feedObj = $this->pdo->query("SELECT * FROM data_feeds WHERE id = '{$idx}' AND is_success = 1");
                $feed = $feedObj->fetch(PDO::FETCH_ASSOC);

                if (empty($feed)) {
                    $idx++;
                    continue;
                }

                $xmlObj = new SimpleXMLElement($feed['response']);

                $items = $xmlObj->Items->Item;
                $totalPage = (int)$xmlObj->Items->TotalPages;
                $itemPagePosition = 1;

                foreach ($items as $key => $item) {

                    $bookValue = [
                        'total_page' => $totalPage,
                        'author_id' => $feed['author_id'],
                        'feed_id' => $feed['id'],
                        'item_page' => $feed['page_number'],
                        'item_page_position' => $itemPagePosition,

                    ];

                    $itemPagePosition++;

                    $bookValue['asin'] = (string) $item->ASIN;


                    $bookObj = $this->pdo->query("SELECT id FROM books WHERE asin = '{$bookValue['asin']}'");

                    $book = $bookObj->fetch(PDO::FETCH_ASSOC);

                    if (!empty($book)) {
                        //book exist in db continue
                        continue;
                    }

                    $bookValue['sales_rank'] = (string) $item->SalesRank;
                    $bookValue['author'] = (string) $item->ItemAttributes->Author;
                    $bookValue['binding'] = (string) $item->ItemAttributes->Binding;
                    $bookValue['format'] = (string) $item->ItemAttributes->Format;
                    $bookValue['is_adult_product'] = (string) $item->ItemAttributes->IsAdultProduct;
                    $bookValue['language_name'] = (string) $item->ItemAttributes->Languages->Language->Name;
                    $bookValue['language_type'] = (string) $item->ItemAttributes->Languages->Language->Type;
                    $bookValue['number_of_pages'] = (string) $item->ItemAttributes->NumberOfPages;
                    $bookValue['product_group'] = (string) $item->ItemAttributes->ProductGroup;
                    $bookValue['product_type_name'] =(string)  $item->ItemAttributes->ProductTypeName;
                    $bookValue['publication_date'] = (string) $item->ItemAttributes->PublicationDate;
                    $bookValue['release_date'] = (string) $item->ItemAttributes->ReleaseDate;
                    $bookValue['title'] = (string) $item->ItemAttributes->Title;
                    $bookValue['detail_page_url'] = (string) $item->DetailPageURL;


                    $bookStmt = $this->pdo->prepare("INSERT INTO books (
                    total_page,author_id,feed_id,item_page,
                    item_page_position,asin,sales_rank,
                    author,binding,format,is_adult_product,
                    language_name,language_type,number_of_pages,
                    product_group,product_type_name,publication_date,
                    release_date,title,detail_page_url
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

                    $bookStmt->execute(array_values($bookValue));
                    $bookId =$this->pdo->lastInsertId();

                    //description editorial_reviews  table
                    $descriptions = $item->EditorialReviews->EditorialReview;
                    foreach ($descriptions as $key => $desc) {

                        $descriptionValues = [];
                        $descriptionValues['book_id'] = $bookId;
                        $descriptionValues['content'] = (string) $desc->Content;
                        $descriptionValues['source'] = (string) $desc->Source;
                        $descriptionValues['is_link_suppressed'] = (string) $desc->IsLinkSuppressed;

                        $descStmt = $this->pdo->prepare("INSERT INTO editorial_reviews (
                          book_id,content,source,is_link_suppressed) VALUES (?,?,?,?)");
                        $descStmt->execute(array_values($descriptionValues));
                    }

                    //similar_products table
                    $similarProducts = $item->SimilarProducts;
                    foreach ($similarProducts as $key => $similar) {
                        $similarValues = [];
                        $similarValues['asin'] = (string) $similar->SimilarProduct->ASIN;
                        $similarValues['title'] = (string) $similar->SimilarProduct->Title;
                        $similarValues['book_id'] = $bookId;

                        $similarProductStmt = $this->pdo->prepare("INSERT INTO similar_products (asin,title,book_id) VALUES (?,?,?)");
                        $similarProductStmt->execute(array_values($similarValues));
                    }
                    $this->terminal->out("{$feed['id']}|{$bookValue['title']}");
                }
                $idx++;
            }

        } catch (\Exception $e) {
            $this->terminal->White()->backgroundRed($e->getMessage());
            exit();
        }

    }


}