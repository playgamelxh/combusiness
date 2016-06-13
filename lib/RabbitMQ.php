<?php

/**
 * 之前工厂网中文站使用的rabbitmq，稍微修改了下，放过来了
 */
class RabbitMQ
{
    private static $instances; //实例
    private static $configs; //配置
    /**
     * 获取虚拟主机配置
     * @param [type] $name   [description]
     * @param [type] $config [description]
     */
    public static function addConfigration($name, $config)
    {
        self::$configs[$name] = $config;
    }

    public static function getInstance($instanceName)
    {
        if (isset(self::$instances[$instanceName]) && is_object(self::$instances[$instanceName])) {
            return self::$instances[$instanceName];
        }
        self::$instances[$instanceName] = new self(self::$configs[$instanceName]);
        return self::$instances[$instanceName];
    }

    public function __construct($config, $num = 10)
    {
        $this->config = $config;
        //建立到rmq服务器链接
        $this->con = new \AMQPConnection($this->config);
        if (!$this->con->connect()) {
            throw new Exception("amqp连接失败!");
        }
        //建立channel
        $this->channel = new \AMQPChannel($this->con);
        $this->channel->qos(0, $num);
        //建立队列
        $this->queue = new \AMQPQueue($this->channel);
    }
    /**
     * 入队列
     * @param [type] $exName    [交换机名]
     * @param [type] $routingKey [路由名]
     * @param [type] $value     [队列的值]
     * @param [type] $dbType     [数据库类型,默认为mysql]
     * 按照此规则生成的默认队列名称为 exName_routeKey_dbType;值为value
     */
    public function set($exName, $routingKey, $value, $dbType = 'mysql')
    {
        //创建交换机,设置交换机名
        $ex = new \AMQPExchange($this->channel);
        $ex->setName($exName);
        $ex->setType(AMQP_EX_TYPE_DIRECT); //广播模式
        $ex->setFlags(AMQP_DURABLE); //交换器进行持久化，即 RabbitMQ 重启后会自动重建
        // $ex->declareExchange();
        //设置队列名
        $this->queue->setName($exName . '_' . $routingKey . '_' . $dbType);
        $this->queue->setFlags(AMQP_DURABLE); //队列进行持久化，即 RabbitMQ 重启后会自动重建
        $this->queue->declareQueue();
        //交换机和路由绑定到队列
        $this->queue->bind($exName, $routingKey);
        //入队列
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $ex->publish($value, $routingKey, AMQP_NOPARAM, array('delivery_mode' => '2'));
//        exit("ccc");
    }

    /**
     * 广播形式写入队列
     *
     * @Author   tianyunzi
     * @DateTime 2015-08-19T18:44:42+0800
     */
    public function setBroadcast($exName, $routingKey, $value)
    {
        //创建交换机
        $ex = new \AMQPExchange($this->channel);
        $ex->setName($exName);
        //入队列
        $ex->publish($value, $routingKey, AMQP_NOPARAM, array('delivery_mode' => '2'));
    }

    /**
     * 读对列
     * @param  [type] $queueName [队列名]
     * @return [type]            [description]
     */
    public function get($queueName)
    {
        //设置队列名
        $this->queue->setName($queueName);
        // $this->queue->setFlags(AMQP_DURABLE); //队列进行持久化，即 RabbitMQ 重启后会自动重建
        // $this->queue->declare();
        // $this->queue->bind($exName, $queueName);
        //读对列
        $messages = $this->queue->get(AMQP_AUTOACK);
        if (is_object($messages)) {
            return $messages->getBody();
        } else {
            return;
        }
    }

    public function __destruct()
    {
        $this->con->disconnect();
    }
}
