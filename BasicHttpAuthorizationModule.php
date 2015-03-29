<?php
namespace Grout\Cyantree\BasicHttpAuthorizationModule;

use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Route;
use Cyantree\Grout\App\Task;
use Grout\Cyantree\BasicHttpAuthorizationModule\Actions\CheckAuthorizationAction;
use Grout\Cyantree\BasicHttpAuthorizationModule\Types\BasicHttpAuthorizationConfig;

class BasicHttpAuthorizationModule extends Module
{
    /** @var BasicHttpAuthorizationConfig */
    public $moduleConfig;

    public function init()
    {
        $this->app->configs->setDefaultConfig($this->id, new BasicHttpAuthorizationConfig());

        /** @var BasicHttpAuthorizationConfig moduleConfig */
        $this->moduleConfig = $this->app->configs->getConfig($this->id);

        foreach ($this->moduleConfig->urls as $url) {
            $this->secureUrl($url);
        }
    }

    public function routeRetrieved(Task $task, Route $route)
    {
        $secured = $route->data->get('secured');
        $whitelisted = $task->data->get('whitelistedByBasicHttpAuthorization');

        if ($secured) {
            if ($whitelisted) {
                return false;

            } else {
                $a = new CheckAuthorizationAction();
                if ($route->data->get('username')) {
                    $a->username = $route->data->get('username');
                    $a->password = $route->data->get('password');

                } else {
                    $a->username = $this->moduleConfig->username;
                    $a->password = $this->moduleConfig->password;
                }

                $a->task = $task;
                $a->module = $this;
                return !$a->execute();
            }

        } elseif ($secured === false) {
            $task->data->set('whitelistedByBasicHttpAuthorization', true);

            return false;
        }

        return true;
    }


    public function secureUrl($url, $username = null, $password = null, $name = null)
    {
        $this->addRoute(
            $url,
            'Pages\SecuredPage',
            array('secured' => true, 'username' => $username, 'password' => $password, 'name' => $name)
        );
    }

    public function whitelistUrl($url)
    {
        $this->addRoute($url, 'Pages\SecuredPage', array('secured' => false), 10);
    }
}
