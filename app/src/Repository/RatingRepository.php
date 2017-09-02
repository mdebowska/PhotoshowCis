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
     * @param string $id Element id
     *
     * @return array|mixed Result
     */

    public function AverageRaringForPhoto($photo_id)
    {
        $query = 'SELECT ROUND(AVG(`value`)) FROM `15_debowska`.`rating` WHERE photo_id= :photo_id';
        $statement = $this->db->prepare($query);
        $statement->bindValue('photo_id', $photo_id, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return !$result ? [] : current($result);
    }



    /**
     *
     * Check if user rated photo.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */

    public function CheckIfUserRatedPhoto($photo_id, $user_id)
    {

        $queryBuilder = $this->queryAll();
        $queryBuilder->where('r.photo_id = :photo_id')
            ->setParameter(':photo_id', $photo_id);
        $results = $queryBuilder->execute()->fetchAll();


        foreach ($results as $result){
            if($result['user_id']===$user_id){
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

        return $queryBuilder->select('r.id', 'r.value', 'r.photo_id', 'r.user_id')
            ->from('rating', 'r');

    }

}