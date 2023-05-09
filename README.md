## bukovel queues control
A convenient way of queues control at the ski resort Bukovel

![desk](https://user-images.githubusercontent.com/104390787/181476123-546c68fe-738e-45dd-9ef9-13686e2142a6.png)
![mob](https://user-images.githubusercontent.com/104390787/181476605-bdae7de3-7d6b-49fa-b727-d8663a7ae5e6.png)

#### Webserver usage 
```
DirectoryIndex start.php
```
```
RewriteEngine On  # for dynamic generating apple-touch-icon.png
```

#### Command line usage
```
php start.php  # crontab task every 5 minutes for updating images from cameras
```

```
php start.php init # mysql table creation for host/hits statistics 
```

![example workflow](https://github.com/lataniuk/bukovel/actions/workflows/main.yml/badge.svg)


