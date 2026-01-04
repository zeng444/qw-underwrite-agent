FROM php:7.4-cli

# 设置工作目录
WORKDIR /data

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# 安装PHP扩展
RUN docker-php-ext-install zip curl

# 安装Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 设置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 创建必要的目录
RUN mkdir -p /data/examples /data/tests

# 设置环境变量
ENV APP_ENV=dev

# 保持容器运行
CMD ["tail", "-f", "/dev/null"]