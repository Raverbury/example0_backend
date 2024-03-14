<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Log\Logger;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function isOwnerOf(Model $model, string $local_key = 'id', string $foreign_key = null): bool
    {
        $default_foreign_key = strtolower(get_class($this));
        $default_foreign_key = explode('\\', $default_foreign_key);
        $default_foreign_key = end($default_foreign_key) . '_id';
        $foreign_key = ($foreign_key != null) ? $foreign_key : $default_foreign_key;
        // error_log(key_exists($foreign_key, $model->toArray())? "yes" : "no");
        // error_log(key_exists($foreign_key, $model->toArray()) ? ($model[$foreign_key] == $this[$local_key]) : false);
        return key_exists($foreign_key, $model->toArray()) ? ($model[$foreign_key] == $this[$local_key]) : false;
    }

    /**
     * Create a user from an array of registration info.
     * $credentials is expected to have these fields: email, name and password.
     * 
     * @return \App\Models\User
     * @throws \Exception
     */
    public static function registerNewUser(array $credentials): User
    {
        $validator = Validator::make(
            $credentials,
            [
                'email' => 'required|email|unique:users|max:256',
                'name' => 'required|max:256|alpha_num:ascii',
                'password' => ['required', 'max:256', Password::min(8)->letters()->mixedCase()->numbers()->symbols()]
            ]
        );

        throw_if($validator->fails(), new Exception($validator->errors()));

        $credentials = $validator->validate();

        try {
            DB::beginTransaction();
            $user = User::create($credentials);
            // User::create(
            // [
            //     'name' => $credentials['name'],
            //     'email' => $credentials['email'],
            //     'password' => Hash::make($credentials['password']),
            // ]);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollback();
            throw $exception;
        }

        return $user;
    }

    /**
     * Log in and return a user token.
     * $credentials is expected to have these fields: email and password.
     * 
     * @return \App\Models\User
     * @throws \Exception
     */
    public static function attemptLogIn(array $credentials): array
    {
        $validator = Validator::make(
            $credentials,
            [
                'email' => 'required|max:256',
                'password' => 'required|max:256'
            ]
        );

        throw_if($validator->fails(), new Exception($validator->errors()));

        $credentials = $validator->validate();

        if (!Auth::attempt($credentials)) {
            throw new Exception("Invalid credentials");
        }

        $user = Auth::user();

        // $todo = Todo::create([
        //     'todo_title' => 'Minekampf',
        //     'todo_text' => 'Dig straight down',
        //     'user_id' => $user->id,
        //     'is_public' => true
        // ]);

        $user->tokens()->delete();
        $token = $user->createToken('test');

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }
}
