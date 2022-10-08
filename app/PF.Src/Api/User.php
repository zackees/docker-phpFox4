<?php

namespace Api;

class User extends \Core\Api
{
    public function put($userId)
    {
        $requests = $this->accept([
            'name'     => 'full_name',
            'email'    => 'email',
            'username' => 'user_name',
        ]);

        $this->get($userId);

        \Phpfox::getService('user.process')->update($userId, $requests);

        return $this->get($userId);
    }

    public function post()
    {
        $this->requires([
            'name',
            'email',
            'password',
        ]);

        \Phpfox::getService('user.validate')->email($this->request('email'));

        $userId = \Phpfox::getService('user.process')->add([
            'full_name' => $this->request('name'),
            'email'     => $this->request('email'),
            'password'  => $this->request('password'),
        ]);

        if (!$userId) {
            throw new \Exception(implode('', \Phpfox_Error::get()));
        }

        return $this->get($userId);
    }

    /**
     * @param mixed $userId
     *
     * @return User\Objects|User\Objects[]
     * @throws \Exception
     */
    public function get($userId = null)
    {
        static $_user = [];

        if (is_array($userId)) {
            return new User\Objects($userId);
        }

        if ($userId !== null && !$userId) {
            return new User\Objects(false);
        }

        if ($userId !== null) {
            $where = ['user_id' => $userId];

            if (!isset($_user[$userId])) {
                $user = $this->db->select('*')->from(':user')->where($where)->execute('getRow');

                if (!isset($user['user_id'])) {
                    if (!$this->isApi()) {
                        return false;
                    }

                    throw new \Exception('User not found:' . $userId);
                }

                $_user[$userId] = $user;
            }
            else {
                $user = $_user[$userId];
            }
        } else {
            $users = [];
            $rows = $this->db->select('*')->from(':user')
                ->where($this->getWhere())
                ->limit($this->getLimit(10))
                ->order($this->getOrder('user_id DESC'))
                ->execute('getRows');
            foreach ($rows as $row) {
                $users[] = new User\Objects($row);
            }

            return $users;
        }

        return new User\Objects($user);
    }
}