# PHP 8.1 Apache ke saath istemal karein
FROM heroku/heroku:22-cnb

# Apne project ki files ko server ke andar /app folder mein copy karein
COPY . /app

# Server ko batayein ke kahan se kaam shuru karna hai
WORKDIR /app

# Server ko batayein ke website ko kaise chalana hai
CMD ["heroku-php-apache2"]
