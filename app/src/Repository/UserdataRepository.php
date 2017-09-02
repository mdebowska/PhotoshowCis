<?php
/**
 * Userdata repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
//----------------------------use Utils\Paginator;

/**
 * Class UserdataRepository.
 *
 * @package Repository
 */
class UserdataRepository
{

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * UserdataRepository constructor.
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

        return $queryBuilder->execute()->fetchAll();
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
        $queryBuilder->where('ud.id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneByUserId($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('ud.user_id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }


    /**
     * Save record.
     *
     * @param array $userdata
     *
     * @return boolean Result
     */
    public function save($userdata) //tak wlasciwie to tylko edycja - dodawanie jest w rejestracji -> UserRepository
    {
        if (isset($userdata['user_id']) && ctype_digit((string) $userdata['user_id'])) {
            // update record

            $id=$userdata['id'];
            unset($userdata['id']);

            return $this->db->update('userdata', $userdata, ['id' => $id]);
        }
    }

    /**
     * Delete record.
     *
     * @param array $photo Photo
     *
     * @return boolean Result
     */
    public function delete($userdata)
    {
        if (isset($userdata['id']) && ctype_digit((string) $userdata['id'])) {
            //delete record
            return $this->db->delete('userdata', ['id'=>$userdata['id']]);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
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

        return $queryBuilder->select('ud.user_id', 'ud.name', 'ud.surname', 'ud.id')
            ->from('userdata', 'ud');
    }
}