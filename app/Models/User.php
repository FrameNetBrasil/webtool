<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'idUser';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'login',
        'email',
        'name',
        'passMD5',
        'auth0IdUser',
        'status',
        'idLanguage',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'passMD5',
    ];

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'idUser';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->getAttribute('idUser');
    }

    /**
     * Get the name of the password field for the user.
     */
    public function getAuthPasswordName(): string
    {
        return 'passMD5';
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return $this->getAttribute('passMD5');
    }

    /**
     * Create User model from repository user object
     */
    public static function fromRepositoryUser(object $repositoryUser): self
    {
        $user = new self;

        // Map all properties from repository user to model
        foreach (get_object_vars($repositoryUser) as $key => $value) {
            $user->setAttribute($key, $value);
        }

        // Ensure the model exists (not a new record)
        $user->exists = true;

        return $user;
    }

    /**
     * Convert this model back to repository-style object
     */
    public function toRepositoryUser(): object
    {
        return (object) $this->attributesToArray();
    }
}
