<?php
namespace app\im\model;

class FriendGroupsModel extends IMModel {
    
    public function members() {
        return $this->belongsToMany("friends_model");
    }
}