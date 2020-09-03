<?php
use Illuminate\Pagination\Paginator;

class UsersController extends Controller {

    public function index($page = 1) {
        Paginator::currentPageResolver(function() use ($page) {
            return $page;
        });

        $users = Users::paginate(15);
        $this->set("users", $users);
        return true;
    }

}