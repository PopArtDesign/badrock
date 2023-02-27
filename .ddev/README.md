# [DDEV](https://ddev.com/)

```sh
ddev config \
  --project-type='wordpress' \
  --disable-settings-management \
  --docroot='public' \
  --create-docroot \
  --upload-dir='uploads'
```

```sh
ddev composer create -y 'popartdesign/badrock:@dev'
```

```sh
ddev exec wp-cli core install \
  --url='"${DDEV_PRIMARY_URL}"' \
  --title='"${DDEV_PROJECT}"' \
  --admin_user='admin' \
  --admin_password='admin' \
  --admin_email='"admin@${DDEV_HOSTNAME}"'
```

```sh
ddev launch
```
