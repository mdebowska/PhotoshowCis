<?php
/**
 * Photo repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class PhotoRepository.
 *
 * @package Repository
 */
class PhotoRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 4;
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
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc');
        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {

        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT p.id) AS total_results')
            ->setMaxResults(1);
        $paginator = new Paginator($this->queryAll()->orderBy('publicationDate', 'desc'), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);
        return $paginator->getCurrentPageResults();
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
        $queryBuilder->where('p.id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();
        if ($result) {
            $result['tags'] = $this->findLinkedTagsIds($result['id']);
        }
        return !$result ? [] : $result;
    }

    /**
     *
     *
     * Find all records from user.
     *
     * @param int $userId id of photos's user
     *
     * @return array|mixed Result
     */
    public function findAllByUser($userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('p.userId = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     *
     *
     * Find all records from user.
     *
     * @param int $userId id of photos's user
     *
     * @return array|mixed Result
     */
    public function findAllByUserIds($userId)
    {
        $queryBuilder = $this->queryAll()->select('p.id');
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('p.userId = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     *
     *
     * Find all records paginated from user.
     *
     * @param int $userId id of photo's user
     * @param int $page Current page number
     * @return array|mixed Result
     */
    public function findAllByUserPaginated($userId, $page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT p.id) AS total_results')
            ->setMaxResults(1)
            ->where('p.userId = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('p.userId = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $paginator = new Paginator($queryBuilder->orderBy('publicationDate', 'desc'), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     *
     *
     * Find all records paginated with tag.
     * @param int $tagId id of tag
     * @param int $page Current page number
     * @return array|mixed Result
     */
    public function findAllWithTagPaginated($tagId, $page = 1)
    {

        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT p.id) AS total_results')
            ->setMaxResults(1)
            ->where('pht.tagId = :tagId')
            ->innerJoin('p', 'photo_has_tag', 'pht', 'p.id = pht.photoId')
            ->setParameter(':tagId', $tagId, \PDO::PARAM_INT);

        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('pht.tagId = :tagId')
            ->innerJoin('p', 'photo_has_tag', 'pht', 'p.id = pht.photoId')
            ->setParameter(':tagId', $tagId, \PDO::PARAM_INT);
        $paginator = new Paginator($queryBuilder->orderBy('publicationDate', 'desc'), $countQueryBuilder);
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

        return $queryBuilder->select('p.id', 'p.title', 'p.source', 'p.userId', 'u.login', 'p.publicationDate')
            ->from('photo', 'p')
            ->innerJoin('p', 'user', 'u', 'p.userId = u.id');
    }


    /**
     * Save record
     * @param array $photo Photo
     * @throws \Exception
     */
    public function save($photo)
    {
        $this->db->beginTransaction();
        unset($photo['login']);
        try {
            $tagsIds = isset($photo['tags']) ? $photo['tags'] : [];
            unset($photo['tags']);

            if (isset($photo['id']) && ctype_digit((string)$photo['id'])) {
                // update record
                $photoId = $photo['id'];
                unset($photo['id']);
                $this->removeLinkedTags($photoId);
                $this->addLinkedTags($photoId, $tagsIds);
                $this->db->update('photo', $photo, ['id' => $photoId]);
            } else {
                // add new record
                $photo['publicationDate'] = date('Y-m-d');

                $this->db->insert('photo', $photo);
                $photoId = $this->db->lastInsertId();
                $this->addLinkedTags($photoId, $tagsIds);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Delete record.
     *
     * @param array $photo Photo
     * @throws \Exception
     */
    public function delete($photo)
    {
        $this->db->beginTransaction();
        unset($photo['login']);
        try {

            if (isset($photo['id']) && ctype_digit((string)$photo['id'])) {
                //delete record
                $id = $photo['id'];
                $this->db->delete('rating', ['photoId' => $id]);
                $this->db->delete('comment', ['photoId' => $id]);
                $this->removeLinkedTags($photo['id']);
                $this->db->delete('photo', ['id' => $id]);

            } else {
                throw new \InvalidArgumentException('Invalid parameter type');
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Finds linked tags Ids.
     *
     * @param int $photoId photo Id
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($photoId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('pht.tagId')
            ->from('photo_has_tag', 'pht')
            ->where('pht.photoId = :photoId')
            ->setParameter(':photoId', $photoId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'tagId') : [];
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $photoId photo Id
     *
     * @return array Result
     */

    public function findLinkedTagsNames($photoId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('t.name', 't.id')
            ->from('tag', 't')
            ->where('pht.photoId = :photoId')
            ->innerJoin('t', 'photo_has_tag', 'pht', 't.id = pht.tagId')
            ->setParameter(':photoId', $photoId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     * Remove linked tags.
     *
     * @param int $photoId photo Id
     *
     * @return boolean Result
     */
    public function removeLinkedTags($photoId)
    {
        return $this->db->delete('photo_has_tag', ['photoId' => $photoId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $photoId Photo Id
     * @param array $tagsIds Tags Ids
     */
    protected function addLinkedTags($photoId, $tagsIds)
    {
        if (!is_array($tagsIds)) {
            $tagsIds = [$tagsIds];
        }

        foreach ($tagsIds as $tagId) {
            $this->db->insert(
                'photo_has_tag',
                [
                    'photoId' => $photoId,
                    'tagId' => $tagId,
                ]
            );
        }
    }
}
