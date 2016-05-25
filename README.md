# OroRedisConfigBundle

Configuration enhancements for the application, based on the OroPlatform that will enable usage of the Redis for caching

### Requirements
Need to install redis-server.  
In Ubuntu Linux it can be installed via
``` bash
    sudo apt-get install redis-server
```

### Installation
From project root need to run
``` bash
    composer require oro/redis-config 
```

### Configuration
In parameters.yml need to add redis config section
``` yaml
    session_handler:    'snc_redis.session.handler'
    redis_dsn_cache:    'redis://password@localhost:6379/0'
    redis_dsn_session:  'redis://password@localhost:6379/1'
    redis_dsn_doctrine: 'redis://password@localhost:6379/2'
```

In config.yml need to activate session handling
``` yaml
framework:
    ...
    session:
        handler_id: snc_redis.session.handler
```

After this need to remove cache.

For more information check [SncRedisBundle Documentation](https://github.com/snc/SncRedisBundle/blob/master/Resources/doc/index.md)!