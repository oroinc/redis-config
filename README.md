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
    redis_dsn_cache: "redis://password@host:port/db"
    redis_dsn_session: "redis://password@host:port/db"
```

After this need to remove cache.
