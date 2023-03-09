# [DDEV](https://ddev.com/)

```sh
ddev config \
  --project-type='wordpress' \
  --disable-settings-management \
  --docroot='public' \
  --create-docroot \
  --upload-dir='wp-content/uploads'
```

```sh
ddev composer create -y 'popartdesign/badrock:@dev'
```

```sh
ddev exec vendor/bin/wp core download
```

```sh
ddev exec vendor/bin/wp core install \
  --url='"${DDEV_PRIMARY_URL}"' \
  --title='"${DDEV_PROJECT}"' \
  --admin_user='admin' \
  --admin_password='admin' \
  --admin_email='"admin@${DDEV_HOSTNAME}"'
```

```sh
ddev exec vendor/bin/wp language core install && \
  ddev exec vendor/bin/wp language plugin install && \
  ddev exec vendor/bin/wp language theme install
```

```sh
ddev launch
```
