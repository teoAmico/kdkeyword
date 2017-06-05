<?php

namespace KDKeywords;

use \PDO;
use \League\CLImate\CLImate;
use DonatelloZa\RakePlus\RakePlus;


class KeywordsExtractor
{
    protected $terminal;
    protected $pdo;

    public function __construct(CLImate $terminal, PDO $pdo)
    {
        $this->terminal = $terminal;
        $this->pdo = $pdo;
    }

    public function extract()
    {

        $maxBookRow = $this->pdo->query("SELECT MAX(id) AS maxId FROM books");

        $idx = 1;
        $maxBook = $maxBookRow->fetch(PDO::FETCH_ASSOC);
        while ($idx <= $maxBook['maxId']) {
            $bookObj = $this->pdo->query("SELECT * FROM books WHERE id = {$idx}");

            $book = $bookObj->fetch(PDO::FETCH_ASSOC);
            if (empty($book)) {
                $idx++;
                continue;
            }

            $keywordsScores = RakePlus::create($book['title'])->scores();

            foreach ($keywordsScores as $keyword => $score) {
                $stm = $this->pdo->prepare("INSERT INTO keywords_scores (keyword,score, language_name) VALUES (?,?,?)");

                $stm->execute([$keyword, $score, $book['language_name']]);
                $this->terminal->out("{$book['language_name']}: {$keyword} - {$score}");
            }
            $idx++;

        }

    }


    public function analysis()
    {
        $keywordRows = $this->pdo->query("SELECT MAX(id) AS maxId FROM keywords_scores");

        $idx = 667100;
        $maxKey = $keywordRows->fetch(PDO::FETCH_ASSOC);
        while ($idx <= $maxKey['maxId']) {

            //only English Ebooks
            $keyObj = $this->pdo->query("SELECT * FROM keywords_scores WHERE id = {$idx} AND (language_name = 'English' OR language_name = '')");
            $key = $keyObj->fetch(PDO::FETCH_ASSOC);
            if (empty($key)) {
                $idx++;
                continue;
            }

            $stmAnalytics = $this->pdo->prepare("SELECT id FROM keywords_analytics WHERE keyword = :keyword");

            $stmAnalytics->bindParam(':keyword', $key['keyword'], PDO::PARAM_STR);

            $stmAnalytics->execute();
            $analyticsResult = $stmAnalytics->fetch(\PDO::FETCH_ASSOC);
            if (!empty($analyticsResult)) {
                $idx++;
                continue;
            }
            $keyword = '%' . $key['keyword'] . '%';

            $bookRowSmt = $this->pdo->prepare("SELECT 
                count(id) AS title_freq, 
                min(CAST(sales_rank AS SIGNED)) AS min_rank,
                max(CAST(sales_rank AS SIGNED)) AS max_rank,
                min(release_date) AS min_rel_date,
                max(release_date) AS max_rel_date,
                min(publication_date) AS min_pub_date,
                max(publication_date) AS max_pub_date,
                min(CAST(number_of_pages AS SIGNED)) AS min_page,
                max(CAST(number_of_pages AS SIGNED)) AS max_page,
                count(author_id) AS author_freq  
                FROM books 
                WHERE title LIKE :keyword");
            $bookRowSmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $bookRowSmt->execute();
            $bookResult = $bookRowSmt->fetch(PDO::FETCH_ASSOC);

            $titleFreq = $bookResult['title_freq'];
            $minSalesRank = $bookResult['min_rank'];
            $maxSalesRank = $bookResult['max_rank'];
            $minReleaseDate = empty($bookResult['min_rel_date']) ? null : $bookResult['min_rel_date'];
            $maxReleaseDate = empty($bookResult['max_rel_date']) ? null : $bookResult['max_rel_date'];
            $minPublishedDate = empty($bookResult['min_pub_date']) ? null : $bookResult['min_pub_date'];
            $maxPublishedDate = empty($bookResult['max_pub_date']) ? null : $bookResult['max_pub_date'];
            $minNumPages = $bookResult['min_page'];
            $maxNumPage = $bookResult['max_page'];
            $numAuthors = $bookResult['author_freq'];

            //how many times it is in title description
            $descFreq = null;
//            $descFreqStm = $this->pdo->prepare("SELECT count(id) AS tot  FROM editorial_reviews WHERE content LIKE :keyword AND is_link_suppressed = 0");
//            $descFreqStm->bindParam(':keyword', $keyword, PDO::PARAM_STR);
//            $descFreqStm->execute();
//            $descFreq = $descFreqStm->fetch(PDO::FETCH_ASSOC)['tot'];

            //How many times it is in similar titles
            $simTitleFreq = null;
            $simTitleFreqStm = $this->pdo->prepare("SELECT count(id) AS tot  FROM similar_products WHERE title LIKE :keyword");
            $simTitleFreqStm->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $simTitleFreqStm->execute();
            $simTitleFreq = $simTitleFreqStm->fetch(PDO::FETCH_ASSOC)['tot'];

            //min keyword score
            $scoreStm = $this->pdo->prepare("SELECT min(CAST(score AS DECIMAL(12,2))) AS min_score,  max(CAST(score AS DECIMAL(12,2))) AS max_score FROM keywords_scores WHERE keyword LIKE :keyword");
            $scoreStm->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $scoreStm->execute();
            $scores = $scoreStm->fetch(PDO::FETCH_ASSOC);
            $minScore = $scores['min_score'];
            $maxScore = $scores['max_score'];

            //created date
            $createdAt = date("Y-m-d H:i:s");

            $analysisStm = $this->pdo->prepare("INSERT INTO keywords_analytics (
              keyword,title_frequency,description_frequency,
              similar_title_frequency,min_sales_rank,max_sales_rank,
              min_release_date,max_release_date,min_published_date,
              max_published_date,min_book_page,max_book_page,
              number_authors,min_score,max_score,
              created_at
            ) VALUES (
            ?,?,?,
            ?,?,?,
            ?,?,?,
            ?,?,?,
            ?,?,?,?)");

            $analysisStm->execute([
                $key['keyword'], $titleFreq, $descFreq,
                $simTitleFreq, $minSalesRank, $maxSalesRank,
                $minReleaseDate, $maxReleaseDate, $minPublishedDate,
                $maxPublishedDate, $minNumPages, $maxNumPage,
                $numAuthors, $minScore, $maxScore,
                $createdAt]);

            $this->terminal->out("{$idx}: {$key['keyword']}");
            $idx++;
        }

    }

    public function fixSalesRank()
    {
        $keywordRows = $this->pdo->query("SELECT MAX(id) AS maxId FROM keywords_analytics");
        $idx = 	50;
        $maxKey = $keywordRows->fetch(PDO::FETCH_ASSOC);
        while ($idx <= $maxKey['maxId']) {
            $keyObj = $this->pdo->query("SELECT * FROM keywords_analytics WHERE id = {$idx}");
            $key = $keyObj->fetch(PDO::FETCH_ASSOC);
            if (empty($key)) {
                $idx++;
                continue;
            }

            $keyword = '%' . $key['keyword'] . '%';

            $minSalesRankStm = $this->pdo->prepare("SELECT min(CAST(sales_rank AS SIGNED)) AS rank  FROM books WHERE title LIKE :keyword AND sales_rank != ''");
            $minSalesRankStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $minSalesRankStm->execute();
            $minSalesRank = $minSalesRankStm->fetch(PDO::FETCH_ASSOC)['rank'];

            //max sales rank
            $maxSalesRankStm = $this->pdo->prepare("SELECT max(CAST(sales_rank AS SIGNED)) AS rank  FROM books WHERE title LIKE :keyword AND sales_rank != ''");
            $maxSalesRankStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $maxSalesRankStm->execute();
            $maxSalesRank = $maxSalesRankStm->fetch(PDO::FETCH_ASSOC)['rank'];

            $analysisStm = $this->pdo->prepare("UPDATE keywords_analytics SET min_sales_rank = ?, max_sales_rank = ? WHERE id = '{$idx}'");
            $analysisStm->execute([$minSalesRank, $maxSalesRank]);

            $this->terminal->out("{$idx}: {$key['keyword']}");
            $idx++;


        }

    }
}