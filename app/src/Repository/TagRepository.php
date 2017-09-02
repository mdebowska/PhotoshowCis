<?php
/**
 * Tag repository.
 */
namespace Repository;
use Silex\Application;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
/**
 * Class TagRepository.
 *
 * @package Repository
 */
class TagRepository
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
     * TagRepository constructor.
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
        $queryBuilder->where('t.id = :id')
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
    public function findIdByName($name)
    {
        $queryBuilder = $this->queryAll()->select('t.id');
        $queryBuilder->where('t.name = :name')
            ->setParameter(':name', $name);
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
        return $queryBuilder->select('t.id', 't.name')
            ->from('tag', 't');
    }
    /**
     * Save record.
     *
     * @return boolean Result
     */
    public function save($tag)  //function to edit and add
    {
        if (isset($tag['id']) && ctype_digit((string) $tag['id'])) { // edit is not used
            // update record
            $id = $tag['id'];
            unset($tag['id']);
            return $this->db->update('tag', $tag, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('tag', $tag);
        }
    }
    /**
     * Delete record.
     *
     * @param array $user
     *
     * @return boolean Result
     */
    public function delete($tag)
    {
        if (isset($tag['id']) && ctype_digit((string) $tag['id'])) {
            //delete record
            $id=$tag['id'];
            return $this->db->delete('tag', ['id'=>$id]);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
        }
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($tag, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.name = :tag')
            ->setParameter(':tag', $tag, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('t.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }


    /**
     * Serch form
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function searchForm(Application $app, Request $request)
    {
        $search=[];
        $form_search = $app['form.factory']->createBuilder(
            SearchType::class,
            $search
        )->getForm();
        $form_search->handleRequest($request);

        if ($form_search->isSubmitted() && $form_search->isValid()) {

            $tag = $form_search->getData();

            if($tag['category']=='photo'){
                $tagRepository = new TagRepository($app['db']);
                $tag=$tagRepository->findIdByName($tag['value']);
                if($tag){
                    return $app->redirect($app['url_generator']->generate('photo_tag', ['id' => $tag['id']]), 301);
                }else{
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.record_not_found',
                        ]
                    );
                }
            }elseif ($tag['category']=='user'){
                $userRepository = new UserRepository($app['db']);
                $user = $form_search->getData();
                $user=$userRepository->findOneByLoginUser($user['value']);
                if($user){
                    return $app->redirect($app['url_generator']->generate('profile_view', ['id' => $user['id']]), 301);
                }
                else{
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.record_not_found',
                        ]
                    );
                }
            }
        }
    }

}




