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

        $idx = 643405;
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

            $stmAnalytics->bindParam(':keyword', $key['keyword'],PDO::PARAM_STR);

            $stmAnalytics->execute();
            $analyticsResult = $stmAnalytics->fetch(\PDO::FETCH_ASSOC);
            if(!empty($analyticsResult)){
                $idx++;
                continue;
            }
            $keyword = '%'. $key['keyword'] . '%';

            //how many times it is in book titles
            $titleFreqStm = $this->pdo->prepare("SELECT count(id) AS tot  FROM books WHERE title LIKE :keyword");
            $titleFreqStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $titleFreqStm->execute();
            $titleFreq = $titleFreqStm->fetch(PDO::FETCH_ASSOC)['tot'];

            //how many times it is in title description
            $descFreqStm = $this->pdo->prepare("SELECT count(id) AS tot  FROM editorial_reviews WHERE content LIKE :keyword AND is_link_suppressed = 0");
            $descFreqStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $descFreqStm->execute();
            $descFreq = $descFreqStm->fetch(PDO::FETCH_ASSOC)['tot'];

            //How many times it is in similar titles
            $simTitleFreqStm = $this->pdo->prepare("SELECT count(id) AS tot  FROM similar_products WHERE title LIKE :keyword");
            $simTitleFreqStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $simTitleFreqStm->execute();
            $simTitleFreq = $simTitleFreqStm->fetch(PDO::FETCH_ASSOC)['tot'];

            //min sales rank
            $minSalesRankStm = $this->pdo->prepare("SELECT min(CAST(sales_rank AS SIGNED)) AS rank  FROM books WHERE title LIKE :keyword AND sales_rank != ''");
            $minSalesRankStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $minSalesRankStm->execute();
            $minSalesRank = $minSalesRankStm->fetch(PDO::FETCH_ASSOC)['rank'];


            //max sales rank
            $maxSalesRankStm = $this->pdo->prepare("SELECT max(CAST(sales_rank AS SIGNED)) AS rank  FROM books WHERE title LIKE :keyword AND sales_rank != ''");
            $maxSalesRankStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $maxSalesRankStm->execute();
            $maxSalesRank = $maxSalesRankStm->fetch(PDO::FETCH_ASSOC)['rank'];

            //min book release date
            $minReleaseDateStm = $this->pdo->prepare("SELECT min(release_date) AS rel_date  FROM books WHERE title LIKE :keyword AND release_date != ''");
            $minReleaseDateStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $minReleaseDateStm->execute();
            $minReleaseDate = $minReleaseDateStm->fetch(PDO::FETCH_ASSOC)['rel_date'];

            //max book release date
            $maxReleaseDateStm = $this->pdo->prepare("SELECT max(release_date) AS rel_date  FROM books WHERE title LIKE :keyword AND release_date != ''");
            $maxReleaseDateStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $maxReleaseDateStm->execute();
            $maxReleaseDate = $maxReleaseDateStm->fetch(PDO::FETCH_ASSOC)['rel_date'];

            //min book published date
            $minPublishedDateStm = $this->pdo->prepare("SELECT min(publication_date) AS pub_date  FROM books WHERE title LIKE :keyword AND publication_date != ''");
            $minPublishedDateStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $minPublishedDateStm->execute();
            $minPublishedDate = $minPublishedDateStm->fetch(PDO::FETCH_ASSOC)['pub_date'];

            //max book published date
            $maxPublishedDateStm = $this->pdo->prepare("SELECT max(publication_date) AS pub_date  FROM books WHERE title LIKE :keyword AND publication_date != ''");
            $maxPublishedDateStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $maxPublishedDateStm->execute();
            $maxPublishedDate = $maxPublishedDateStm->fetch(PDO::FETCH_ASSOC)['pub_date'];

            //min number of pages
            $minNumPagesStm = $this->pdo->prepare("SELECT min(CAST(number_of_pages AS SIGNED)) AS page  FROM books WHERE title LIKE :keyword AND number_of_pages != ''");
            $minNumPagesStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $minNumPagesStm->execute();
            $minNumPages = $minNumPagesStm->fetch(PDO::FETCH_ASSOC)['page'];

            //max number of pages
            $maxNumPagesStm = $this->pdo->prepare("SELECT max(CAST(number_of_pages AS SIGNED)) AS page  FROM books WHERE title LIKE :keyword AND number_of_pages != ''");
            $maxNumPagesStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $maxNumPagesStm->execute();
            $maxNumPage = $maxNumPagesStm->fetch(PDO::FETCH_ASSOC)['page'];

            //number of authors
            $numAuthorsStm = $this->pdo->prepare("SELECT count(author_id) AS tot FROM books WHERE title LIKE :keyword AND author_id != ''  GROUP BY author_id ");
            $numAuthorsStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $numAuthorsStm->execute();
            $numAuthors = $numAuthorsStm->fetch(PDO::FETCH_ASSOC)['tot'];

            //min keyword score
            $minScoreStm = $this->pdo->prepare("SELECT min(score) AS score  FROM keywords_scores WHERE keyword LIKE :keyword");
            $minScoreStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $minScoreStm->execute();
            $minScore = $minScoreStm->fetch(PDO::FETCH_ASSOC)['score'];

            //max keyword score
            $maxScoreStm = $this->pdo->prepare("SELECT max(score) AS score  FROM keywords_scores WHERE keyword LIKE :keyword");
            $maxScoreStm->bindParam(':keyword', $keyword,PDO::PARAM_STR);
            $maxScoreStm->execute();
            $maxScore = $maxScoreStm->fetch(PDO::FETCH_ASSOC)['score'];

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
                $key['keyword'],$titleFreq,$descFreq,
                $simTitleFreq,$minSalesRank,$maxSalesRank,
                $minReleaseDate, $maxReleaseDate, $minPublishedDate,
                $maxPublishedDate, $minNumPages, $maxNumPage,
                $numAuthors, $minScore,$maxScore,
                $createdAt]);

            $this->terminal->out("{$idx}: {$key['keyword']}");
            $idx++;
        }

    }
}