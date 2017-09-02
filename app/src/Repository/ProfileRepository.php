<?php
/**
 * Photo repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class ProfileRepository.
 *
 * @package Repository
 */
class ProfileRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 30;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * ProfileRepository constructor.
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
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAllUsers()
    {
        $queryBuilder = $this->queryAllUsers();

        return $queryBuilder->execute()->fetchAll();
    }


    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllUsersPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAllUsers()
            ->select('COUNT(DISTINCT t.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
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
        $queryBuilder->where('u.id = :id')
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
    public function findOneByIdUser($id)
    {
        $queryBuilder = $this->queryAllUsers();
        $queryBuilder->where('u.id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }




    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('u.id', 'u.login', 'u.mail', 'u.password', 'ud.name', 'ud.surname')
            ->from('user', 'u')
            ->innerJoin('u', 'userdata', 'ud', 'u.id = ud.user_id');
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllUsers()
    {
        $queryBuilder = $this->db->createQueryBuilder();
        return $queryBuilder->select('id', 'login', 'password', 'mail')
            ->from('user', 'u');
    }






    /**
     * Save record.
     *
     * @param array $profile Profile
     *
     * @return boolean Result
     */
    public function save($profile) //tak wlasciwie to tylko edycja - dodawanie jest w rejestracji -> UserRepository
    {
        if (isset($profile['id']) && ctype_digit((string) $profile['id'])) {
            // update record
            $id = $profile['id'];
            unset($profile['id']);
            $profile_data = $profile;

            unset($profile['name']);
            unset($profile['surname']);

            unset($profile_data['login']);
            unset($profile_data['password']);
            unset($profile_data['mail']);

            return $this->db->update('user', $profile, ['id' => $id]) && $this->db->update('userdata', $profile_data, ['user_id' => $id]);
        } else {
            // add new record - to sie nigdy nie wykonuje
            $user['role_id']=2;
            $userdata['user_id']=$user['id'];
            return $this->db->insert('user', $user)&& $this->db->insert('userdata', $userdata);
        }
    }

}