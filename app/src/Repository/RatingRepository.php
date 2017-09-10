<?php
/**
 * Rating repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class RatingRepository.
 *
 * @package Repository
 */
class RatingRepository
{

    protected $db;

    /**
     * RatingRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     *
     * Find average records for photo.
     *
     * @param string $photoId id of photo
     *
     * @return array|mixed Result
     */

    public function AverageRaringForPhoto($photoId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('r.photoId = :photoId')
            ->select("avg(r.value)")
            ->setParameter(':photoId', $photoId);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : current($result);
    }



    /**
     *
     * Check if user rated photo.
     *
     * @param int $photoId id of photo
     * @param int $userId id of user
     * @return array|mixed Result
     */
    public function CheckIfUserRatedPhoto($photoId, $userId)
    {

        $queryBuilder = $this->queryAll();
        $queryBuilder->where('r.photoId = :photoId')
            ->setParameter(':photoId', $photoId);
        $results = $queryBuilder->execute()->fetchAll();


        foreach ($results as $result){
            if($result['userId']===$userId){
                return true;
            }
        }
        return false;
    }


    /**
     * Save record.
     *
     * @param array $rating Rating
     *
     * @return boolean Result
     */
    public function save($rating)
    {

        if (isset($rating['id']) && ctype_digit((string) $rating['id'])) { //jesli juz oceniles to nie mozesz tego zmienic

            return 0;
        } else {
            // add new record
            return $this->db->insert('rating', $rating);
        }
    }


    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('r.id', 'r.value', 'r.photoId', 'r.userId')
            ->from('rating', 'r');

    }

}