
# simple-petition
Petition made for Slovenian Student Union (ŠOS) in 2023. Written in PHP using Taliwind CSS. The contents of HTML file can be updated according to your needs. Petition includes Google Anaytics tag and Google ReCAPTCHA. It also allows only one signature per IP (this can of course be a problem so remove it if you don't need it. Malicious entries could still happen if you remove this unique IP constraint.

# Requirements
A server running PHP and MySQL database with two tables:
```
CREATE  TABLE `signatures` (
`id`  int(11) NOT NULL,
`author`  varchar(255) NOT NULL,
`email`  varchar(255) DEFAULT  NULL,
`institution`  varchar(255) DEFAULT  NULL,
`consent`  tinyint(1) NOT NULL  DEFAULT  0,
`ts`  timestamp  NULL  DEFAULT  current_timestamp(),
`IP`  varchar(15) DEFAULT  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_slovenian_ci;

ALTER  TABLE  `signatures`
ADD  PRIMARY KEY (`id`);

ALTER  TABLE  `signatures`
MODIFY  `id`  int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13284;
COMMIT;

CREATE  TABLE `special_signature` (
`ts`  datetime  NOT NULL  DEFAULT  current_timestamp(),
`author_field`  varchar(255) DEFAULT  NULL,
`email_field`  varchar(255) DEFAULT  NULL,
`institution`  varchar(255) DEFAULT  NULL,
`IP`  varchar(15) DEFAULT  NULL,
`comment`  varchar(255) DEFAULT  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_slovenian_ci;

ALTER  TABLE  `special_signature`
ADD  PRIMARY KEY (`ts`);
COMMIT;
```
# Misc
The database uses slovenian charset so update your charset according to your needs. There are also some lines of code you have to alter (DB schema name, DB URL, Google Analytics and Google ReCAPTCHA tags/API keys...). Look at code comments for the lines you have to adjust. There are also some anchor tags in `index.php` that lead nowhere (FAQ section, GDPR...). Create your own FAQ and GDPR agreements.
