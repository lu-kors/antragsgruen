<?php

namespace app\async\protocol;

use app\async\models\Userdata;

class ProtocolHandler
{
    /** @var Configuration */
    private $configuration;

    /**
     * ProtocolHandler constructor.
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param \Swoole\WebSocket\Server $_server
     * @param \Swoole\WebSocket\Frame $frame
     */
    public function onMessage(\Swoole\WebSocket\Server $_server, \Swoole\WebSocket\Frame $frame)
    {
        $data    = json_decode($frame->data, true);
        $session = Session::getSessionForFd($_server, $frame);

        if (!$data || !isset($data['op'])) {
            echo "Got invalid data package: " . $frame->data . "\n";
            return;
        }
        switch ($data['op']) {
            case 'auth':
                if (!isset($data['subdomain']) || !isset($data['path']) || !isset($data['auth'])) {
                    $session->sendDataToClient([
                        'op'  => 'auth_error',
                        'msg' => 'Missing parameter',
                    ]);
                    return;
                }
                $subdomain = $data['subdomain'];
                $path      = $data['path'];
                $this->authenticate(
                    $subdomain,
                    $path,
                    $data['auth'],
                    function (Userdata $ret) use ($session, $subdomain, $path) {
                        echo 'Welcome: ' . $ret->username . "\n";
                        $session->setUser($ret, $subdomain, $path);
                        $session->sendDataToClient([
                            'op'   => 'auth_success',
                            'user' => $ret->jsonSerialize(),
                        ]);
                    },
                    function ($ret, $err) use ($session) {
                        echo "\nError: \n";
                        $session->sendDataToClient([
                            'op'  => 'auth_error',
                            'msg' => $err,
                        ]);
                    }
                );
                break;

            case 'subscribe':
                if (!$session->isAuthenticated()) {
                    echo "Session is not authenticated\n";
                    $session->sendDataToClient([
                        'op'  => 'error',
                        'msg' => 'Session is not authenticated',
                    ]);
                } else {
                    $channel = Channel::getSpoolFromId($session->getSubdomain(), $session->getPath(), $data['channel']);
                    $channel->addSession($session);
                    $session->addSubscribedChannel($session->getSubdomain(), $session->getPath(), $data['channel']);
                    $session->sendDataToClient([
                        'op'           => 'subscribed',
                        'consultation' => $data['consultation'],
                        'channel'      => $data['channel'],
                    ]);
                    $channel->loadInitialData($session);
                }
                break;
        }
    }

    /**
     * @param string $subdomain
     * @param string $path
     * @param string $cookie
     * @param callable $success
     * @param callable $error
     */
    public function authenticate(string $subdomain, string $path, string $cookie, callable $success, callable $error)
    {
        try {
            $cli = new \Swoole\Coroutine\Http\Client('127.0.0.1', 80);
            $cli->set(['timeout' => 3.0]);
            echo $this->configuration->getHostname($subdomain) . " - "  . $path . "\n";
            $cli->setHeaders([
                'Host'       => $this->configuration->getHostname($subdomain),
                'User-Agent' => 'Swoole Client',
                'Accept'     => 'application/json',
                'Cookie'     => 'PHPSESSID=' . $cookie,
            ]);
            $cli->get('/' . $path . '/async/user');
            if ($cli->statusCode === 200) {
                $user = new Userdata($cli->body);
                $success($user);
            } else {
                var_dump($cli);
                $error($cli->statusCode, $cli->body);
            }
        } catch (\Exception $e) {
            $error(500, $e->getMessage());
        }
    }

    /**
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return bool
     */
    public function websocketHandshake(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        //自定定握手规则，没有设置则用系统内置的（只支持version:13的）
        if (!isset($request->header['sec-websocket-key'])) {
            //'Bad protocol implementation: it is not RFC6455.'
            $response->end();
            return false;
        }
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
            || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
        ) {
            //Header Sec-WebSocket-Key is illegal;
            $response->end();
            return false;
        }

        $uuid    = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $key     = base64_encode(sha1($request->header['sec-websocket-key'] . $uuid, true));
        $headers = array(
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $key,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off',
        );
        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }
        $response->status(101);
        $response->end();
        global $server;
        $connId = $request->fd;
        $server->defer(function () use ($connId, $server) {
            $server->push($connId, json_encode(['op' => 'hello']));
        });

        return true;
    }

    /**
     * @param \Swoole\WebSocket\Server $_server
     * @param \Swoole\Http\Request $request
     */
    public function onOpen(\Swoole\WebSocket\Server $_server, \Swoole\Http\Request $request)
    {
        echo "server#{$_server->worker_pid}: handshake success with fd#{$request->fd}\n";
        var_dump($_server->exist($request->fd), $_server->getClientInfo($request->fd));
    }

    /**
     * @param \Swoole\WebSocket\Server $_server
     * @param int $fd
     */
    public function onClose(\Swoole\WebSocket\Server $_server, $fd)
    {
        Session::destroySession($fd);
    }
}