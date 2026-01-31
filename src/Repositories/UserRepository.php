<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\User;

/**
 * User Repository
 * 
 * Handles database operations for User entities
 */
class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new user
     * 
     * @param User $user
     * @return bool
     * @throws PDOException
     */
    public function create(User $user): bool
    {
        $sql = "INSERT INTO users (id, username, email, password_hash, role, created_at, updated_at) 
                VALUES (:id, :username, :email, :password_hash, :role, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $user->getId(),
                ':username' => $user->getUsername(),
                ':email' => $user->getEmail(),
                ':password_hash' => $user->getPasswordHash(),
                ':role' => $user->getRole(),
                ':created_at' => $user->getCreatedAt(),
                ':updated_at' => $user->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create user: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find user by ID
     * 
     * @param string $id
     * @return User|null
     */
    public function findById(string $id): ?User
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find user by username
     * 
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find user by email
     * 
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find user by username or email
     * 
     * @param string $usernameOrEmail
     * @return User|null
     */
    public function findByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        $sql = "SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username' => $usernameOrEmail,
            ':email' => $usernameOrEmail
        ]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Update user
     * 
     * @param User $user
     * @return bool
     * @throws PDOException
     */
    public function update(User $user): bool
    {
        $sql = "UPDATE users 
                SET username = :username, 
                    email = :email, 
                    password_hash = :password_hash, 
                    role = :role,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $user->getId(),
                ':username' => $user->getUsername(),
                ':email' => $user->getEmail(),
                ':password_hash' => $user->getPasswordHash(),
                ':role' => $user->getRole(),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update user: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Delete user
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Check if username exists
     * 
     * @param string $username
     * @return bool
     */
    public function usernameExists(string $username): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if email exists
     * 
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all users by role
     * 
     * @param string $role
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        $sql = "SELECT * FROM users WHERE role = :role ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => $role]);
        
        $users = [];
        while ($data = $stmt->fetch()) {
            $users[] = $this->hydrate($data);
        }
        
        return $users;
    }

    /**
     * Get all users
     * 
     * @return User[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        
        $users = [];
        while ($data = $stmt->fetch()) {
            $users[] = $this->hydrate($data);
        }
        
        return $users;
    }

    /**
     * Hydrate user from database row
     * 
     * @param array $data
     * @return User
     */
    private function hydrate(array $data): User
    {
        return new User(
            $data['id'],
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
            $data['created_at'],
            $data['updated_at']
        );
    }
    
    /**
     * Store password reset token
     * 
     * @param string $userId
     * @param string $tokenHash
     * @param string $expiry
     * @return bool
     */
    public function storePasswordResetToken(string $userId, string $tokenHash, string $expiry): bool
    {
        $sql = "INSERT INTO password_resets (user_id, token_hash, expiry, created_at) 
                VALUES (:user_id, :token_hash, :expiry, :created_at)
                ON DUPLICATE KEY UPDATE 
                token_hash = :token_hash, 
                expiry = :expiry, 
                created_at = :created_at";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expiry' => $expiry,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get password reset token data
     * 
     * @param string $userId
     * @return array|null
     */
    public function getPasswordResetToken(string $userId): ?array
    {
        $sql = "SELECT * FROM password_resets WHERE user_id = :user_id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $data = $stmt->fetch();
        return $data ?: null;
    }
    
    /**
     * Delete password reset token
     * 
     * @param string $userId
     * @return bool
     */
    public function deletePasswordResetToken(string $userId): bool
    {
        $sql = "DELETE FROM password_resets WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
    
    /**
     * Update user password
     * 
     * @param string $userId
     * @param string $newPasswordHash
     * @return bool
     */
    public function updatePassword(string $userId, string $newPasswordHash): bool
    {
        $sql = "UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':password_hash' => $newPasswordHash,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
