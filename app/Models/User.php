<?php
/**
 * Created by PhpStorm.
 * User: korisnik
 * Date: 04-Feb-18
 * Time: 21:47
 */

namespace App\Models;

class UserModel
{
    public $korisnicko_ime;
    public $prezime;
    public $email;
    public $password;
    public $username;
    public $uloga_id;
    private $table = 'user';

    public function insert()
    {
        return \DB::table($this->table)
            ->insertGetId([
                'korisnicko_ime' => $this->korisnicko_ime,
                'prezime' => $this->prezime,
                'username' => $this->username,
                'password' => md5($this->password),
                'email' => $this->email,
                'uloga_id' => $this->uloga_id,
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
                'korisnicko_ime' => $this->korisnicko_ime,
                'prezime' => $this->prezime,
                'password' => md5($this->password),
                'email' => $this->email,
                'username' => $this->username,
                'uloga_id' => $this->uloga_id
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