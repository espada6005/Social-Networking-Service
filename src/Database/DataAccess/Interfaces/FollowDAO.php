<?php

namespace Database\DataAccess\Interfaces;

use Models\Follow;

interface FollowDAO {

    public function createFollow(Follow $follow): bool;
    public function getFollowers(int $user_id, int $limit, int $offset): array;
    public function getFollowerCount(int $user_id): int;
    public function getFollowees(int $user_id, int $limit, int $offset): array;
    public function getFolloweeCount(int $user_id): int;
    public function isFollower(int $user_id, int $follower_id): bool;
    public function isFollowee(int $user_id, int $followee_id): bool;
    public function deletefollow(int $user_id, int $followee_id): bool;

}
