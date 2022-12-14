<?php

namespace Core\Route;

use Core\Cache;
use Core\Registry;

/**
 * Class Controller
 *
 * @package Core\Route
 */
class Controller
{
    /**
     * @var
     */
    public static $active;
    /**
     * @var
     */
    public static $activeId;
    /**
     * @var
     */
    public static $name;
    /**
     * @var bool
     */
    public static $isApi = false;

    /**
     * @var \Core\Request
     */
    private $_request;
    /**
     * @var \Core\Url
     */
    private $_url;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->_request = new \Core\Request();
        $this->_url = new \Core\Url();
    }

    /**
     * @return $this|array|bool|mixed
     * @throws
     * @throws \Exception
     * @throws mixed
     */
    public function get()
    {
        if ($this->_request->segment(1) == 'api') {
            self::$isApi = true;
            new \Core\Route('/api/token/:token', function ($controller, $token) {
                $cachedToken = (new Cache())->get('auth_token_' . $token);
                (new Cache())->del('auth_token_' . $token);
                if (!isset($cachedToken['headers'])) {
                    return [
                        'error' => 'Token does not exist.',
                    ];
                }

                return $cachedToken;
            });
        }

        $content = false;
        $uri = trim($this->_url->uri(), '/');

        $found = \Core\Route::match($uri);

        if(!$found){
            $found =  \Core\Route::match($uri = \Phpfox::getLib('url')->reverseRewriteUrl($uri));
        }
        
        if ($found) {
            $r = $found;
            $r['route'] = $uri;
            self::$name = $r;

            try {
                if (isset($r['auth']) && $r['auth'] === true) {
                    \Phpfox::isUser(true);
                }

                if (isset($r['accept'])) {
                    if (!is_array($r['accept'])) {
                        $r['accept'] = [$r['accept']];
                    }

                    if (!in_array($this->_request->method(), $r['accept'])) {
                        throw error(_p('Method not support.'));
                    }
                }


                if (isset($r['run'])) {


                    $Controller = new \Core\Controller($r['path'] . 'views', $uri);

                    if (isset($r['short']) && $r['short'] === true) {
                        $pass = [];
                    } else {
                        $pass = [$Controller];
                    }
                    if (isset($r['args'])) {
                        $pass = array_merge($pass, $r['args']);
                    }

                    $content = call_user_func_array($r['run'], $pass);

                } else {
                    if (isset($r['url'])) {
                        $App = \Phpfox::getCoreApp()->get($r['id']);

                        $innerHTML = function ($xml) {
                            $innerXML = '';
                            foreach (dom_import_simplexml($xml)->childNodes as $child) {
                                if ($child->nodeName == 'api') {
                                    continue;
                                }
                                $innerXML .= $child->ownerDocument->saveHTML($child);
                            }

                            return $innerXML;
                        };

                        $Template = \Phpfox_Template::instance();

                        $http = new \Core\HTTP($r['url']);

                        \Core\Event::trigger('external_controller', $http);

                        $token = md5(uniqid() . PHPFOX_TIME);
                        $headers = [
                            'API_TOKEN'     => $token,
                            'API_CLIENT_ID' => PHPFOX_LICENSE_ID,
                            'API_HOME'      => \Phpfox_Url::instance()->makeUrl(''),
                            'API_ENDPOINT'  => \Phpfox_Url::instance()->makeUrl('api'),
                            'API_URI'       => \Phpfox_Url::instance()->getUri(),
                            'API_USER'      => json_encode((\Phpfox::isUser() ? user() : [])),
                        ];

                        (new \Core\Cache())->set('auth_token_' . $token, [
                            'created' => PHPFOX_TIME,
                            'headers' => $headers,
                            'auth'    => [
                                'user' => $App->auth->id,
                                'pw'   => $App->auth->key,
                            ],
                        ]);

                        $http->auth($App->auth->id, $App->auth->key)->using($this->_request->all());
                        foreach ($headers as $key => $value) {
                            $http->header($key, $value);
                        }
                        $response = $http->call($_SERVER['REQUEST_METHOD']);

                        $parse = function ($thisContent, $isJson = false) {
                            $thisContent = preg_replace_callback('/<user-([a-z\-]+) ([a-zA-Z\-0-9="\' ]+)><\/user-\\1>/is', function ($matches) use ($isJson) {
                                $type = str_replace('-', '_', trim($matches[1]));
                                $parts = explode(' ', trim($matches[2]));
                                $keys = [];
                                foreach ($parts as $part) {
                                    $part = trim($part);
                                    if (substr($part, 0, 5) != 'data-') {
                                        continue;
                                    }

                                    $sub = explode('=', str_replace('data-', '', $part));
                                    $keys[$sub[0]] = (isset($sub[1]) ? trim(trim($sub[1], '"'), "'") : '');
                                }

                                if (!isset($keys['id'])) {
                                    return '';
                                }

                                $userId = $keys['id'];
                                try {
                                    $user = (new \Api\User())->get($userId);
                                } catch (\Exception $e) {
                                    return '';
                                }

                                if (property_exists($user, $type)) {
                                    $return = $user->{$type};
                                    if ($isJson) {
                                        $return = str_replace('"', "'", $return);
                                    }

                                    return $return;
                                }

                                return '';

                            }, $thisContent);

                            return $thisContent;
                        };

                        if (is_object($response)) {
                            header('Content-type: application/json');

                            if (isset($response->run)) {

                                $response->run = str_replace('[PF_DOUBLE_QUOTE]', '\'', $response->run);
                                $response->run = $parse($response->run, true);
                            }

                            echo json_encode($response, JSON_PRETTY_PRINT);
                            exit;
                        }

                        if (empty($response)) {
                            return false;
                        }

                        $doc = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        $doc->loadHTML(mb_convert_encoding($response, 'HTML-ENTITIES', 'UTF-8'));
                        $xml = $doc->saveXML($doc);

                        $xml = @simplexml_load_string($xml);

                        if ($xml === false) {
                            $xml = new \stdClass();
                            $xml->body = $response;
                        } else {
                            if (!isset($xml->body)) {
                                $xml = new \stdClass();
                                $xml->body = $response;
                            }
                        }

                        if (isset($xml->body->api)) {
                            if (isset($xml->body->api->section)) {
                                $Template->setBreadCrumb($xml->body->api->section->name, \Phpfox_Url::instance()->makeUrl($xml->body->api->section->url));
                            }

                            if (isset($xml->body->api->h1)) {
                                $Template->setBreadCrumb($xml->body->api->h1->name, \Phpfox_Url::instance()->makeUrl($xml->body->api->h1->url), true);
                            }

                            if (isset($xml->body->api->menu)) {
                                $Template->setSubMenu($innerHTML($xml->body->api->menu));
                            }

                            // unset($xml->body->api);
                        }

                        $Controller = new \Core\Controller();
                        if (isset($xml->head)) {
                            $Controller->title('' . $xml->head->title);

                            $attributes = function ($keys) {
                                $attributes = '';
                                foreach ($keys as $key => $value) {
                                    $attributes .= ' ' . $key . '="' . $value . '" ';
                                }

                                return $attributes;
                            };
                            foreach ((array)$xml->head as $type => $data) {
                                switch ((string)$type) {
                                    case 'style':
                                        $Template->setHeader('<' . $type . '>' . (string)$data . '</' . $type . '>');
                                        break;
                                    case 'link':
                                        $Template->delayedHeaders[] = '<' . $type . ' ' . $attributes($data->attributes()) . '>';
                                        break;
                                }
                            }
                        }

                        $thisContent = (is_string($xml->body) ? $xml->body : $innerHTML($xml->body));
                        $thisContent = $parse($thisContent);

                        if ($this->_request->isPost()) {
                            echo $thisContent;
                            exit;
                        } else {
                            $content = $Controller->render('@Base/blank.html', [
                                'content' => $thisContent,
                            ]);
                        }
                    } else {
                        if (isset($r['call'])) {
                            $parts = explode('@', $r['call']);
                            if (!isset($parts[1])) {
                                $parts[1] = $this->_request->method();
                            }

                            if (preg_match('/^([a-zA-Z]+)_Component_Controller_([a-zA-Z]+)$/i', $parts[0], $matches)) {
                                if (isset($r['on_start'])) {
                                    call_user_func($r['on_start']);
                                }

                                return new Module($matches[1], $matches[2]);
                            }

                            $Reflection = new \ReflectionClass($parts[0]);
                            $Controller = $Reflection->newInstance((isset($r['path']) ? $r['path'] . 'views' : null));

                            $args = $r['args'];

                            try {
                                $content = call_user_func_array([$Controller, $parts[1]], $args);
                            } catch (\Exception $e) {
                                if (self::$isApi) {
                                    http_response_code(400);
                                    $content = [
                                        'error' => [
                                            'message' => $e->getMessage(),
                                        ],
                                    ];
                                } else {
                                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                                }
                            }
                        } else {
                            if (isset($r['api']) && $r['api']) {
                                try {
                                    $api = \Phpfox::getService($r['api_service']);
                                    $transport = \Phpfox::getService(Registry::get('PHPFOX_EXTERNAL_API_TRANSPORT'));

                                    if (!$api || !($api instanceof \Core\Api\ApiServiceBase)) {
                                        $content = [
                                            'status'   => 'failed',
                                            'data'     => [],
                                            'messages' => [
                                                _p('Cannot find API Service for this request.'),
                                            ],
                                        ];
                                    } else {
                                        $content = $api->process($r, $transport, strtolower($this->_request->method()));
                                    }
                                } catch (\Exception $e) {
                                    http_response_code(400);

                                    $errors = empty($e->getMessage()) ? \Phpfox_Error::get() : [$e->getMessage()];
                                    $content = [
                                        'status'   => 'failed',
                                        'data'     => [],
                                        'messages' => $errors,
                                    ];
                                }
                                self::$isApi = true;

                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                if ($this->_request->isPost()) {
                    $errors = \Core\Exception::getErrors(true);
                    if (!$errors) {
                        $errors = '<div class="error_message">' . $e->getMessage() . '</div>';
                    }
                    $content = ['error' => $errors];
                } else {
                    if (!$e->getCode()) {
                        $Controller = new \Core\Controller();
                        $content = $Controller->render('@Base/blank.html', [
                            'content' => '<div class="error_message">' . $e->getMessage() . '</div>',
                        ]);
                    } else {
                        throw new \Exception($e->getMessage(), $e->getCode(), $e);
                    }
                }
            }

            if (is_array($content) || self::$isApi) {
                header('Content-type: application/json');
                echo json_encode($content, JSON_PRETTY_PRINT);
                exit;
            }

            if (empty($content) || $this->_request->isPost() || (is_object($content) && $content instanceof \Core\jQuery)) {
                if (is_object($content) && $content instanceof \Core\jQuery) {
                    header('Content-type: application/json');
                    echo json_encode([
                        'run' => (string)$content,
                    ]);
                }

                exit;
            }
        }

        return $content;
    }
}