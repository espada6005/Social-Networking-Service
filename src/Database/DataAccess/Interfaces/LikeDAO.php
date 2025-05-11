<?php

namespace Database\DataAccess\Interfaces;

use Models\Like;

interface LikeDAO {
    
    public function create(Like $like): bool;

    public function exists(int $userId, int $postId): bool;
    
    public function delete(int $userId, int $postId): bool;

}
