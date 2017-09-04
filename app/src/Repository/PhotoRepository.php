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
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findAllByUser($user_id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('p.user_id = :user_id')
            ->setParameter(':user_id', $user_id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();
        return !$result ? [] : $result;
    }
    /**
     *
     *
     * Find all records from user.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findAllByUserIds($user_id)
    {
        $queryBuilder = $this->queryAll()->select('p.id');
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('p.user_id = :user_id')
            ->setParameter(':user_id', $user_id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();
        return !$result ? [] : $result;
    }
    /**
     *
     *
     * Find all records paginated from user.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findAllByUserPaginated($user_id, $page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT p.id) AS total_results')
            ->setMaxResults(1)
            ->where('p.user_id = :user_id')
            ->setParameter(':user_id', $user_id, \PDO::PARAM_INT);
        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('p.user_id = :user_id')
            ->setParameter(':user_id', $user_id, \PDO::PARAM_INT);
        $paginator = new Paginator( $queryBuilder->orderBy('publicationDate', 'desc'), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);
        return $paginator->getCurrentPageResults();
    }

    /**
     *
     *
     * Find all records paginated with tag.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findAllWithTagPaginated($tag_id, $page = 1)
    {

        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT p.id) AS total_results')
            ->setMaxResults(1)
            ->where('pht.tag_id = :tag_id')
            ->innerJoin('p', 'photo_has_tag', 'pht', 'p.id = pht.photo_id')
            ->setParameter(':tag_id', $tag_id, \PDO::PARAM_INT);

        $queryBuilder = $this->queryAll();
        $queryBuilder->orderBy('publicationDate', 'desc')
            ->where('pht.tag_id = :tag_id')
            ->innerJoin('p', 'photo_has_tag', 'pht', 'p.id = pht.photo_id')
            ->setParameter(':tag_id', $tag_id, \PDO::PARAM_INT);
        $paginator = new Paginator( $queryBuilder->orderBy('publicationDate', 'desc'), $countQueryBuilder);
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
        return $queryBuilder->select('p.id', 'p.title', 'p.source', 'p.user_id', 'u.login', 'p.publicationDate')
            ->from('photo', 'p')
            ->innerJoin('p', 'user', 'u', 'p.user_id = u.id')
            ;
    }


    /**
     * Save record with tag.
     *
     * @param array $bookmark Photo
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($photo)
    {
        $this->db->beginTransaction();
        unset($photo['login']);
        try {

            $tagsIds = isset($photo['tags']) ? $photo['tags'] : [];
            unset($photo['tags']);

            if (isset($photo['id']) && ctype_digit((string) $photo['id'])) {
                // update record
                $photoId = $photo['id'];
                unset($photo['id']);
                $this->removeLinkedTags($photoId);
                $this->addLinkedTags($photoId, $tagsIds);
                $this->db->update('photo', $photo, ['id' => $photoId]);
            } else {
                // add new record
                $photo['publicationDate']=date('Y-m-d');

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
     *
     * @return boolean Result
     */
    public function delete($photo)
    {
        $this->db->beginTransaction();
        unset($photo['login']);
        try {

            if (isset($photo['id']) && ctype_digit((string) $photo['id'])) {
                //delete record
                $id=$photo['id'];
                $this->db->delete('rating', ['photo_id'=>$id]);
                $this->db->delete('comment', ['photo_id'=>$id]);
                $this->removeLinkedTags($photo['id']);
                $this->db->delete('photo', ['id'=>$id]);
	
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
     * Delete record.
     *
     * @param array $photo Photo
     *
     * @return boolean Result
     */
//    public function delete($photo)
//    {
//        if (isset($photo['id']) && ctype_digit((string) $photo['id'])) {
//            //delete record
//            $id=$photo['id'];
//          $this->db->delete('rating', ['photo_id'=>$id]);
//            $this->db->delete('comment', ['photo_id'=>$id]);
//            $this->removeLinkedTags($photo['id']);
//            return $this->db->delete('photo', ['id'=>$id]);
//        } else {
//            throw new \InvalidArgumentException('Invalid parameter type');
//        }
//    }


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
            ->select('pht.tag_id')
            ->from('photo_has_tag', 'pht')
            ->where('pht.photo_id = :photo_id')
            ->setParameter(':photo_id', $photoId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'tag_id') : [];
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
            ->where('pht.photo_id = :photo_id')
            ->innerJoin('t', 'photo_has_tag', 'pht', 't.id = pht.tag_id')
            ->setParameter(':photo_id', $photoId, \PDO::PARAM_INT);
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
        return $this->db->delete('photo_has_tag', ['photo_id' => $photoId]);
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
                    'photo_id' => $photoId,
                    'tag_id' => $tagId,
                ]
            );
        }
    }
}