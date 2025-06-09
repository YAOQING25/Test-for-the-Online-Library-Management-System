<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\Config\TestDatabase;

class FilterRecordsTest extends TestCase
{
    private $db;
    private $uniquePrefix;

    protected function setUp(): void
    {
        $this->db = TestDatabase::getInstance();
        $this->db->beginTransaction();
        
        // Generate unique prefix for test data
        $this->uniquePrefix = 'Test_' . uniqid() . '_';
        
        // Add multiple test books
        $this->addTestBooks(15);
    }

    private function addTestBooks($count)
    {
        // Add test category
        $stmt = $this->db->prepare(
            'INSERT INTO tblcategory (CategoryName, Status, CreationDate) 
             VALUES (:name, 1, NOW())'
        );
        $stmt->execute(['name' => $this->uniquePrefix . 'Category']);
        $categoryId = $this->db->lastInsertId();
        
        // Add test author
        $stmt = $this->db->prepare(
            'INSERT INTO tblauthors (AuthorName, CreationDate) 
             VALUES (:name, NOW())'
        );
        $stmt->execute(['name' => $this->uniquePrefix . 'Author']);
        $authorId = $this->db->lastInsertId();
        
        // Add test books
        $stmt = $this->db->prepare(
            'INSERT INTO tblbooks 
             (BookName, CatId, AuthorId, ISBNNumber, BookPrice, RegDate) 
             VALUES (:name, :catId, :authorId, :isbn, :price, NOW())'
        );
        
        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([
                'name' => $this->uniquePrefix . 'Book' . $i,
                'catId' => $categoryId,
                'authorId' => $authorId,
                'isbn' => '978' . str_pad($i, 10, '0', STR_PAD_LEFT),
                'price' => 10 + ($i * 2)
            ]);
        }
    }

    public function testFilterRecordsPerPage()
    {
        // Test different page sizes
        $pageSizes = [5, 10, 15];
        
        foreach ($pageSizes as $pageSize) {
            // Get books with pagination
            $stmt = $this->db->prepare('
                SELECT * FROM tblbooks 
                WHERE BookName LIKE :prefix 
                ORDER BY id ASC 
                LIMIT :limit
            ');
            $stmt->bindValue(':prefix', $this->uniquePrefix . '%', \PDO::PARAM_STR);
            $stmt->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $stmt->execute();
            $books = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Verify correct number of books returned
            $this->assertCount($pageSize, $books);
        }
    }

    public function testPagination()
    {
        $pageSize = 5;
        $totalBooks = 15;
        $totalPages = ceil($totalBooks / $pageSize);
        
        for ($page = 1; $page <= $totalPages; $page++) {
            $offset = ($page - 1) * $pageSize;
            
            // Get books for current page
            $stmt = $this->db->prepare('
                SELECT * FROM tblbooks 
                WHERE BookName LIKE :prefix 
                ORDER BY id ASC 
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':prefix', $this->uniquePrefix . '%', \PDO::PARAM_STR);
            $stmt->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $books = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Calculate expected number of books on this page
            $expectedCount = ($page < $totalPages) ? $pageSize : ($totalBooks % $pageSize ?: $pageSize);
            
            // Verify correct number of books returned
            $this->assertCount($expectedCount, $books);
            
            // Verify book IDs are in the expected range
            $firstBookIndex = ($page - 1) * $pageSize + 1;
            $lastBookIndex = min($page * $pageSize, $totalBooks);
            
            for ($i = 0; $i < count($books); $i++) {
                $expectedBookName = $this->uniquePrefix . 'Book' . ($firstBookIndex + $i);
                $this->assertEquals($expectedBookName, $books[$i]['BookName']);
            }
        }
    }

    public function testEmptyResults()
    {
        // Test with a non-existent prefix
        $stmt = $this->db->prepare('
            SELECT * FROM tblbooks 
            WHERE BookName LIKE :prefix 
            LIMIT 10
        ');
        $stmt->bindValue(':prefix', 'NonExistent%', \PDO::PARAM_STR);
        $stmt->execute();
        $books = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Verify no books returned
        $this->assertCount(0, $books);
    }

    protected function tearDown(): void
    {
        if ($this->db->isInTransaction()) {
            $this->db->rollbackTransaction();
        }
    }
} 