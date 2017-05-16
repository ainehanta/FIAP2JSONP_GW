FROM alpine:3.5

RUN mkdir -p /usr/src/app
WORKDIR /usr/src/app

RUN apk add --update --no-cache $(apk search -U -q 'php7*') ca-certificates

CMD php7 -S 0.0.0.0:8000
