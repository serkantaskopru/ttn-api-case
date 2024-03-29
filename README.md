Bu projenin amacı, bir e-ticaret platformu için siparişlerin oluşturulması, düzenlenmesi, kampanyaların uygulanması ve sipariş detaylarının görüntülenmesi için bir API geliştirmektir.

## Bilgilendirme

Sipariş oluştururken email alanı tanımlanırsa test amaçlı bir email gönderilir.

Gönderilen mail kuyruklama ile çalışmaktadır.

Sipariş detayları önbellek sistemi ile çalışmaktadır.

Siparişlerdeki indirimler aratoplamı baz alarak çalışır.

Hediye kahve ürünü net tutar 3000 TL üzeri olunca tanımlanır

Sipariş düzenleme, silme gibi işlemlerde kullanıcı kontrolü veya rol kontrolü yoktur.

## Gereksinimler

PHP 8+

Mysql 5+

Composer

## Kurulum

```
git clone https://github.com/serkantaskopru/ttn-api-case.git
cd ttn-api-case
cp .env.example .env
```
.env dosyasını kendi bilgilerinize göre düzenleyin, ardından aşağıdaki komutları çalıştırın
```
composer install
php artisan key:generate
php artisan storage:link
php artisan cache:clear
php artisan migrate:fresh --seed
php artisan serve
php artisan queue:work --queue=high,default
```



## API Dökümantasyonu

[Postman üzerinden dökümantasyona erişmek için tıklayın.](https://documenter.getpostman.com/view/32308603/2s9YsT4TGG#0a2884d3-80a0-4327-b924-76ba5a1a354a)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.