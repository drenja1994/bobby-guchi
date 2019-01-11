<?php


namespace App\Models;

class User
{
    public $firstname;
    public $lastname;
    public $username;
    public $password;
    public $email;
    public $ranking_id;
    private $table = 'user';

    public function insert()
    {
        return \DB::table($this->table)
            ->insertGetId([
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'username' => $this->username,
                'password' => md5($this->password),
                'email' => $this->email,
                'ranking_id' => $this->ranking_id,
            ]);
    }

    public function selectAll()
    {
        return \DB::table($this->table)
            ->get();
    }

    public function selectOne($id)
    {
        return \DB::table($this->table)
            ->where("id", $id)->first();
    }

    public function delete($id)
    {
        return \DB::table($this->table)
            ->delete($id);
    }

    public function update($id)
    {
        return \DB::table($this->table)
            ->where("id", $id)
            ->update([
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'password' => md5($this->password),
                'email' => $this->email,
                'username' => $this->username,
                'ranking_id' => $this->ranking_id_id
            ]);
    }

    public function login()
    {
        return \DB::table($this->table)
            ->where([
                ['username', '=', $this->username],
                ['password', '=',md5($this->password)]
            ])->select("user.*")
            ->get()->first();
    }
}