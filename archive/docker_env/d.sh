#!/bin/bash

args=($@)

# 配置部分
thinkCMF_dir="D:\Applications\work\ThinkCMF"
httpd_conf="D:\Applications\docker_env\httpd_docker_php.conf"
php_conf="D:\Applications\docker_env\php.ini"
workerman_dir="D:\Applications\work\WSPush"
# ssl cer
serverCrt="D:\Applications\httpd\2285556_im.5dx.ink.crt"
serverKey='D:\Applications\httpd\2285556_im.5dx.ink.key'
# 设置到容器的环境变量
env=(
    "REDIS_HOST=192.168.0.87"
    "REDIS_PORT=6379"
    "MYSQL_HOST=192.168.0.68"
    "MYSQL_PORT=3306"
)
# ------

if test $# -eq 0
then
    docker ps -a
    exit 0
fi


function d_php() {
    containerId=$2
    operate=$3
    case $1 in
    "start")
        echo "start:"
        # 环境变量
        env_args=""
        for env_entry in ${env[@]}
        do
            env_args="$env_args -e $env_entry"
        done
        # 启动容器
        containerId=`docker run -d $env_args -p 80:80 -p 443:443 -p 8080:8080 -v "$thinkCMF_dir:/app" -v "$workerman_dir:/gateway" webdevops/php-apache-dev`
        re=$?
        echo $re
        if test $re -ne 0
        then
            exit $re
        fi
        # containerId=`docker run -d --network host $env_args -v "$thinkCMF_dir:/app" -v "$workerman_dir:/gateway" webdevops/php-apache-dev`
        # 替换成自己的证书文件
        if test -f "$serverCrt" -a -f "$serverKey"
        then
            docker cp "$serverCrt" $containerId:/opt/docker/etc/httpd/ssl/server.crt
            docker cp "$serverKey" $containerId:/opt/docker/etc/httpd/ssl/server.key
        fi

        d_php reload $containerId >> /dev/null

        containerId=${containerId:0:12}
        # 输出容器 id 和运行 workerman 的脚本
        echo "docker exec $containerId php /gateway/start.php start"
        echo "$containerId"
    ;;
    "reload")
        if test -z "$containerId"
        then
            containerId=`docker ps -a | grep 'webdevops/php-apache-dev' | grep 'Up' | awk '{print $1}'`
        fi
        # httpd 配置文件
        if test -f "$httpd_conf"
        then
            docker cp  "$httpd_conf" $containerId:/opt/docker/etc/httpd/vhost.conf
            docker exec $containerId service httpd restart
        fi
        # php 配置文件
        if test -f "$php_conf"
        then
            docker cp "$php_conf" $containerId:/opt/docker/etc/php/php.ini
            # echo "docker exec $containerId kill -sigusr2 `ps aux | grep 'php-fpm: master' | head -n 1 | awk '{print $2}'`"
            # echo '`ps aux | grep "php-fpm: master" | head -n 1 | awk "{print $2}"`'
            pidFpm=`docker exec $containerId cat var/run/php-fpm.pid`
            docker exec $containerId kill -sigusr2 $pidFpm
        fi
    ;;
    "stop")
        echo "stop:"
        if test -z "$containerId"
        then
            docker stop `docker ps -a | grep 'webdevops/php-apache-dev' | grep 'Up' | awk '{print $1}'` >> /dev/null
            docker rm `docker ps -a | grep 'webdevops/php-apache-dev' | awk '{print $1}'`
        else
            docker stop $containerId >> /dev/null
            docker rm $containerId
        fi
    ;;
    "restart")
        d_php stop $containerId
        d_php start $containerId
    ;;
    esac
    return 0;
}

function d_redis() {
    case $1 in 
    "start")
        containerId=`docker run -dp 6379:6379 redis`
        echo ${containerId:0:12}
    ;;
    "into")
        docker exec -i $2 redis-cli
    ;;
    "restart")
        containerIds=`docker ps -a | grep 'redis' | awk '{print $1}'`
        docker stop $containerIds
        docker rm $containerIds > /dev/null
        d_redis start
    ;;
    *)
        containerIds=`docker ps -a | grep 'redis' | awk '{print $1}'`
        echo $containerIds
    ;;
    esac
    return 0;
}

# function d_gateway() {
#     containerId=$2

#     case $1 in
#     "start")
#         docker exec $containerId php /gateway/start.php start
#     ;;
#     "restart")
#         docker exec $containerId php /gateway/start.php stop
#         docker exec $containerId php /gateway/start.php start
#     ;;
#     esac
#     return 0
# }

function d_turn() {
    case $1 in
    "start")
        containerId=`docker run -d --network=host instrumentisto/coturn`
        echo $containerId
    ;;
    esac
    return 0;
}

function d_test() {
    echo $@
    return 0;
}

case $1 in
    "rm")
        # 不带其它参数删除所有
        if test $# -eq 1
        then 
            docker rm `docker ps -a | grep Exited | awk '{print $1}'`
            exit 0
        fi
    ;;
    "stop")
        containerIds=`docker ps -a | grep Up | awk '{print $1}'`
        docker stop $containerIds
    ;;
    "into")
        docker exec -it $2 /bin/bash
    ;;
esac
# 直接找到对应函数执行
command="d_$1"
pass_args=${args[@]:1}

if eval "$command $pass_args"
then
    exit 0
fi

# docker $@

exit 1