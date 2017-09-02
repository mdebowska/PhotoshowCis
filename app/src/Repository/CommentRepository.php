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

    /**
     *
     *
     * Find all comment of photo.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findAllOfPhoto($photo_id)
    {
        $queryBuilder = $this->queryAllWithAuthor();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('c.photo_id = :photo_id')
            ->setParameter(':photo_id', $photo_id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllOfPhotoPaginated($photo_id, $page = 1)
    {

        $countQueryBuilder = $this->queryAllOfPhoto($photo_id)
            ->select('COUNT(DISTINCT c.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAllOfPhoto($photo_id)->orderBy('publicationDate', 'desc'), $countQueryBuilder);

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

        return $queryBuilder->select('c.id', 'c.text', 'c.publicationDate', 'c.user_id', 'c.photo_id')
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

        return $queryBuilder->select('c.id', 'c.text', 'c.publicationDate', 'c.user_id', 'c.photo_id', 'u.login')
            ->from('comment', 'c')
            ->innerJoin('c', 'user', 'u', 'c.user_id = u.id');
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllOfPhoto($photo_id)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('c.id', 'c.text', 'c.publicationDate', 'c.user_id', 'c.photo_id', 'u.login')
            ->from('comment', 'c')
            ->where('c.photo_id = :photo_id')
            ->setParameter(':photo_id', $photo_id, \PDO::PARAM_INT)
            ->innerJoin('c', 'user', 'u', 'c.user_id = u.id');
    }

    /**
     * Save record.
     *
     * @param array $photo Photo
     *
     * @return boolean Result
     */
    public function save($comment)
    {

        if (isset($comment['id']) && ctype_digit((string) $comment['id'])) {
            // update record
            $id = $comment['id'];
            unset($comment['id']);

            return $this->db->update('comment', $comment, ['id' => $id]);
        } else {
            // add new record
            $comment['publicationDate']=date('Y-m-d');
            return $this->db->insert('comment', $comment);
        }
    }


    /**
     * Delete record.
     *
     * @param array $photo Photo
     *
     * @return boolean Result
     */

    public function delete($comment)
    {
        if (isset($comment['id']) && ctype_digit((string) $comment['id'])) {
            //delete record
            $id=$comment['id'];
            return $this->db->delete('comment', ['id'=>$id]);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
        }
    }
}

