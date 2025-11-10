
# V2Board Docker 版本安装指南

本指南将帮助您快速安装和部署 V2Board。

## 系统要求

- Linux 系统（推荐 Ubuntu 22.04+ 或 Debian 11+）
- Docker 和 Docker Compose
- 至少 1GB 内存
- 至少 10GB 磁盘空间

## 安装步骤

### 1. 安装 Docker

```bash
curl -sSL https://get.docker.com | bash
```

### 2. 获取项目文件

```bash
git clone -b dev --depth 1 https://github.com/rebecca554owen/v2board
cd v2board
```

### 3. 准备配置文件

```bash
cp compose-sample.yml compose.yml
```

### 4. 安装依赖并初始化

```bash
docker compose run -it --rm v2board sh init.sh
```

### 5. 启动服务

```bash
docker compose up -d
```

## 验证安装

安装完成后，您可以通过以下方式验证服务是否正常运行：

```bash
docker compose logs -f
```

## 访问面板

默认情况下，您可以通过以下地址访问管理面板：

- 前端：`http://your-server-ip:7002`


## 常见问题

### 端口冲突

如果遇到端口冲突，请修改 `compose.yaml` 文件中的端口配置。

## 更新

要更新 V2Board，请运行：

```bash
docker compose pull && \
docker compose run -it --rm v2board sh update.sh && \
docker compose up -d
```


**注意：** 请确保在生产环境中修改默认密码和配置。