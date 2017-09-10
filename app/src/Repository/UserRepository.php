<?php
/**
 * User repository. Just id, logn, email and password
 */
namespace Repository;

use Silex\Application;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;


/**
 * Class UserRepository.
 *
 * @package Repository
 */
class UserRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 6;

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
            ->select('COUNT(DISTINCT u.id) AS total_results')
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
    public function findOneByLoginUser($login)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.login = :login')
            ->setParameter(':login', $login);
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

        return $queryBuilder->select('u.id', 'u.login', 'u.mail')
            ->from('user', 'u');
    }
    /**
     * Save record.
     *
     * @return boolean Result
     */
    public function save(Application $app, $user)  //function to edit and add
    {
        if (isset($user['id']) && ctype_digit((string) $user['id'])) {
            // update record
            $id = $user['id'];
            unset($user['id']);
            $user['password'] = $app['security.encoder.bcrypt']->encodePassword($user['password'], '');
            return $this->db->update('user', $user, ['id' => $id]);
        } else {
            // add new record
            $user['role_id']=2;
            $user['password'] = $app['security.encoder.bcrypt']->encodePassword($user['password'], '');
            return $this->db->insert('user', $user);
        }
    }
    public function saveEmptyData($user)  //
    {

            $userdata['user_id']=$user['id'];
            $userdata['name']='';
            $userdata['surname']='';
            return $this->db->insert('userdata', $userdata); //$this->db->insert('user', $user)&&

    }



    /**
     * Delete User with Userdata, All photos by user, All retiring.
     *
     * @param array $user
     *
     * @return boolean Result
     */
    public function deleteUser(Application $app, $user)
    {

	$this->db->beginTransaction();
        try {

        	if (isset($user['id']) && ctype_digit((string) $user['id'])) {

            	    $id=$user['id'];
	            $photoRepository = new PhotoRepository($app['db']);
	            $photo_ids=$photoRepository->findAllByUserIds($user['id']);

	            $this->db->delete('rating', ['user_id'=>$id]);
	            $this->db->delete('comment', ['user_id'=>$id]);

	            if($photo_ids){
	                foreach ($photo_ids as $photo_id){
	                    $this->db->delete('rating', ['photo_id'=>$photo_id['id']]);
	                    $this->db->delete('comment', ['photo_id'=>$photo_id['id']]);
	                    $photoRepository->removeLinkedTags($photo_id['id']);
	                }
	            }
	            $this->db->delete('photo', ['user_id'=>$id]);
	            $this->db->delete('userdata', ['user_id'=>$id]);
	            $this->db->delete('user', ['id'=>$id]);

	
	        } else {
        	   	 throw new \InvalidArgumentException('Invalid parameter type');
	        }
		$this->db->commit();
	} catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
       }

    }


    /**
     * Delete record.
     *
     * @param array $user
     *
     * @return boolean Result
     */
//    public function delete($user)
//    {
//        if (isset($user['id']) && ctype_digit((string) $user['id'])) {
//
//          return $this->db->delete('user', ['id'=>$user['id']]);
//      } else {
 //         throw new \InvalidArgumentException('Invalid parameter type');
//      }
//  }



    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.password')
                ->from('user', 'u')
                ->where('u.login = :login')
                ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($user_id)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('user', 'u')
                ->innerJoin('u', 'role', 'r', 'u.role_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $user_id, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }


    /**
     * Gets logged user.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getLoggedUser(Application $app)
    {
        $logged_user=[];

        $token = $app['security.token_storage']->getToken();


        if (null !== $token) {
            $user = $token->getUser();
            $user=$this->getUserByLogin($user);
            $logged_user=$user;

            if($logged_user){

                $logged_user['id'] = $user['id'];
                $logged_role = $this->getUserRoles($logged_user['id']);
                $logged_user['role'] = $logged_role[0];
            }
        }
        return $logged_user;

    }



    /**
     * Find for uniqueness.
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($login, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.login = :login')
            ->setParameter(':login', $login, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('u.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }


}