<?php
/**
 * Comment repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class CommentRepository.
 *
 * @package Repository
 */
class CommentRepository
{

    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 3;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * PhotoRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }


    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('c.id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /** FindAllOfPhoto($photoId)
     *
     * @param int $photoId Id of searched photo
     * @return array
     */
    public function findAllOfPhoto($photoId)
    {
        $queryBuilder = $this->queryAllWithAuthor();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('c.photoId = :photoId')
            ->setParameter(':photoId', $photoId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     * Get records paginated.
     * @param int $photoId Id of searched photo
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllOfPhotoPaginated($photoId, $page = 1)
    {

        $countQueryBuilder = $this->queryAllOfPhoto($photoId)
            ->select('COUNT(DISTINCT c.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAllOfPhoto($photoId)->orderBy('publicationDate', 'desc'), $countQueryBuilder);

        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }


    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('c.id', 'c.text', 'c.publicationDate', 'c.userId', 'c.photoId')
            ->from('comment', 'c');
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllWithAuthor()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('c.id', 'c.text', 'c.publicationDate', 'c.userId', 'c.photoId', 'u.login')
            ->from('comment', 'c')
            ->innerJoin('c', 'user', 'u', 'c.userId = u.id');
    }

    /**
     * Query all records.
     * @param int $photoId id of Photo
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllOfPhoto($photoId)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('c.id', 'c.text', 'c.publicationDate', 'c.userId', 'c.photoId', 'u.login')
            ->from('comment', 'c')
            ->where('c.photoId = :photoId')
            ->setParameter(':photoId', $photoId, \PDO::PARAM_INT)
            ->innerJoin('c', 'user', 'u', 'c.userId = u.id');
    }

    /**
     * Save record.
     *
     * @param array $comment Comment
     *
     * @return boolean Result
     */
    public function save($comment)
    {

        if (isset($comment['id']) && ctype_digit((string)$comment['id'])) {
            // update record
            $id = $comment['id'];
            unset($comment['id']);

            return $this->db->update('comment', $comment, ['id' => $id]);
        } else {
            // add new record
            $comment['publicationDate'] = date('Y-m-d');
            return $this->db->insert('comment', $comment);
        }
    }


    /**
     * Delete record.
     *
     * @param array $comment Comment
     *
     * @return boolean Result
     */

    public function delete($comment)
    {
        if (isset($comment['id']) && ctype_digit((string)$comment['id'])) {
            //delete record
            $id = $comment['id'];
            return $this->db->delete('comment', ['id' => $id]);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
        }
    }
}

