<?php

namespace Adldap\Laravel\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class SyncPassword
{
    /**
     * The users model.
     *
     * @var Model
     */
    protected $model;

    /**
     * The users credentials.
     *
     * @var array
     */
    protected $credentials;

    /**
     * Constructor.
     *
     * @param Model $model
     * @param array $credentials
     */
    public function __construct(Model $model, array $credentials = [])
    {
        $this->model = $model;
        $this->credentials = $credentials;
    }

    /**
     * Sets the password on the users model.
     *
     * @return Model
     */
    public function handle()
    {
        if ($this->hasPasswordColumn()) {
            $password = $this->canSync() ?
                $this->password() : Str::random();

            if ($this->passwordNeedsUpdate($password)) {
                $this->applyPassword($password);
            }
        }

        return $this->model;
    }

    /**
     * Applies the password to the users model.
     *
     * @param string $password
     *
     * @return void
     */
    protected function applyPassword($password)
    {
        $this->model->setAttribute(
            $this->column(),
            $this->model->hasSetMutator($this->column()) ? $password : bcrypt($password)
        );
    }

    /**
     * Determines if the current model requires a password update.
     *
     * This checks if the model does not current have a
     * password, or if the password fails a hash check.
     *
     * @param string|null $password
     *
     * @return bool
     */
    protected function passwordNeedsUpdate($password = null)
    {
        $current = $this->currentPassword();

        return is_null($current) || ! Hash::check($password, $current);
    }

    /**
     * Determines if the database schema contains a password column.
     *
     * @return bool
     */
    protected function hasPasswordColumn()
    {
        $column = $this->column();

        return $column ? Schema::hasColumn($this->model->getTable(), $column) : false;
    }

    /**
     * Retrieves the password from the users credentials.
     *
     * @return string|null
     */
    protected function password()
    {
        return array_get($this->credentials, 'password');
    }

    /**
     * Retrieves the current models hashed password.
     *
     * @return string|null
     */
    protected function currentPassword()
    {
        return $this->model->getAttribute($this->column());
    }

    /**
     * Determines if we're able to sync the models password with the current credentials.
     *
     * @return bool
     */
    protected function canSync()
    {
        return array_key_exists('password', $this->credentials) && $this->syncing();
    }

    /**
     * Determines if the password should be synchronized.
     *
     * @return bool
     */
    protected function syncing()
    {
        return Config::get('adldap_auth.passwords.sync', false);
    }

    /**
     * Retrieves the password column to use.
     *
     * @return string
     */
    protected function column()
    {
        return Config::get('adldap_auth.passwords.column', 'password');
    }
}