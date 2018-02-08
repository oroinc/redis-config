# OroRedisConfigBundle

Configuration enhancements for the application, based on the OroPlatform that will enable usage of the Redis for caching

## Configure Redis Servers

> Oro architecture require a least two instances of redis server. First instance will be used as fast persistent storage for sessions and second as LRU Cache storage.

### Ubuntu Xenial or later

Install redis-server via apt
``` bash
sudo apt install redis-server
```

#### Configure second instance as lru memory cache

Create folders for **redis-cache** server  
```bash
sudo mkdir -p /var/lib/redis-cache /var/log/redis-cache /var/run/redis-cache
sudo chown redis:redis /var/lib/redis-cache /var/log/redis-cache /var/run/redis-cache
```

Create tmpfiles config

```bash
echo "d /run/redis-cache 2775 redis redis -" | sudo tee /usr/lib/tmpfiles.d/redis-cache-server.conf
```

Copy original configs for second server
```bash
sudo cp -rp /etc/redis /etc/redis-cache
```

Replace all content in `/etc/redis-cache/redis.conf` with:  

```
daemonize yes
pidfile /var/run/redis-cache/redis-server.pid
port 6380
tcp-backlog 511
bind 127.0.0.1
timeout 0
tcp-keepalive 0
loglevel notice
logfile /var/log/redis-cache/redis-server.log
databases 16
maxmemory 256mb
maxmemory-policy allkeys-lru
stop-writes-on-bgsave-error no
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis-cache
slave-serve-stale-data yes
slave-read-only yes
repl-diskless-sync no
repl-diskless-sync-delay 5
repl-disable-tcp-nodelay no
slave-priority 100
appendonly no
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
aof-load-truncated yes
lua-time-limit 5000
slowlog-log-slower-than 10000
slowlog-max-len 128
latency-monitor-threshold 0
notify-keyspace-events ""
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-entries 512
list-max-ziplist-value 64
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
hll-sparse-max-bytes 3000
activerehashing yes
client-output-buffer-limit normal 0 0 0
client-output-buffer-limit slave 256mb 64mb 60
client-output-buffer-limit pubsub 32mb 8mb 60
hz 10
aof-rewrite-incremental-fsync yes
```

Create systemd unit `/lib/systemd/system/redis-cache-server.service` for **redis-cache** with following contents:
```yaml
[Unit]
Description=Redis-Cache
After=network.target

[Service]
Type=forking
ExecStart=/usr/bin/redis-server /etc/redis-cache/redis.conf
PIDFile=/var/run/redis-cache/redis-server.pid
TimeoutStopSec=0
Restart=always
User=redis
Group=redis

ExecStartPre=-/bin/run-parts --verbose /etc/redis-cache/redis-server.pre-up.d
ExecStartPost=-/bin/run-parts --verbose /etc/redis-cache/redis-server.post-up.d
ExecStop=-/bin/run-parts --verbose /etc/redis-cache/redis-server.pre-down.d
ExecStop=/bin/kill -s TERM $MAINPID
ExecStopPost=-/bin/run-parts --verbose /etc/redis-cache/redis-server.post-down.d

PrivateTmp=yes
PrivateDevices=yes
ProtectHome=yes
ReadOnlyDirectories=/
ReadWriteDirectories=-/var/lib/redis-cache
ReadWriteDirectories=-/var/log/redis-cache
ReadWriteDirectories=-/var/run/redis-cache
CapabilityBoundingSet=~CAP_SYS_PTRACE

ProtectSystem=true
ReadWriteDirectories=-/etc/redis-cache

[Install]
WantedBy=multi-user.target
Alias=redis-cache.service
```

Enable and start systemd unit

```bash
systemctl enable redis-cache-server.service
systemctl start redis-cache
```

Verify status of new service  

```bash
systemctl status redis-cache
```

### Install Package
Require package via composer
``` bash
composer require oro/redis-config 
```

### Configuration of Application for standalone redis setup
Update parameters.yml with next options:
``` yaml
session_handler:    'snc_redis.session.handler'
redis_dsn_session:  'redis://127.0.0.1:6379/0'
redis_dsn_cache:    'redis://127.0.0.1:6380/0'
redis_dsn_doctrine: 'redis://127.0.0.1:6380/1'
redis_setup: 'standalone'
```


### Configuration of Application for redis cluster setup
Update parameters.yml with next options:
````yaml
session_handler:    'snc_redis.session.handler'
redis_dsn_session:  ['redis://127.0.0.1:6379/0?alias=master','redis://127.0.0.1:6380/0']
redis_dsn_cache:    ['redis://127.0.0.1:6381/0?alias=master','redis://127.0.0.1:6382/0']
redis_dsn_doctrine: ['redis://127.0.0.1:6381/1?alias=master','redis://127.0.0.1:6382/0']
redis_setup: 'cluster'
````


### Configuration of Application for usage sentinel redis setup
Update parameters.yml with next options:
````yaml
session_handler:    'snc_redis.session.handler'
redis_dsn_session:  ['redis://127.0.0.1:26379/0','redis://127.0.0.1:26379/0']
redis_dsn_cache:    ['redis://127.0.0.1:26379/1','redis://127.0.0.1:26379/1']
redis_dsn_doctrine: ['redis://127.0.0.1:26379/2','redis://127.0.0.1:26379/2']
redis_setup: 'sentinel'
redis_sentinel_master_name: 'mymaster'
redis_sentinel_prefer_slave: '127.0.0.1'
````
In this case it is required to provide redis-sentinel endpoints with db numbers for redis_dsn_session,redis_dsn_cache,redis_dsn_doctrine. 
In parameter redis_sentinel_master_name master service name, which configured in sentinel.conf, needs to be provided
```yaml
sentinel monitor mymaster 127.0.0.1 2
```
Parameter redis_*sentinel_prefer_slave* is responsible for selection preferable slave node via IP address in case if cluster has 
a few slaves and it needs to connect to specific one. 
Also, please pay attention, you have to set up at least 2 sentinel endpoints, otherwise integration will not work.

### Related links

- [SncRedisBundle Documentation](https://github.com/snc/SncRedisBundle/blob/master/Resources/doc/index.md)
- [PedisBundle Documentation](https://github.com/nrk/predis)
- [Redis Sentinel Documentation](https://redis.io/topics/sentinel)
- [Redis cluster tutorial](https://redis.io/topics/cluster-tutorial)