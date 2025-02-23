<?php
declare(strict_types=1);

namespace Cocoon\Database\Contracts;

/**
 * Interface RepositoryInterface
 * @package Cocoon\Database\Contracts
 */
interface RepositoryInterface
{
    /**
     * @param string $columns
     * @param null $paginate
     * @param string $orderByField
     * @param string $order
     * @return mixed
     */
    public function all($columns = '', $paginate = null, $orderByField = 'id', $order = 'desc');

    /**
     * @param $id
     * @param string $columns
     * @return mixed
     */
    public function find($id, $columns = '');

    /**
     * @param array $data
     * @return mixed
     */
    public function save($data = []);

    /**
     * @param $id
     * @param array $data
     * @return mixed
     */
    public function update($id, $data = []);

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id);
}
