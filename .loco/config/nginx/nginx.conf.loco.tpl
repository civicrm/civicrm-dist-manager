daemon            off;
worker_processes  2;
pid               {{LOCO_SVC_VAR}}/nginx.pid;

events {
    use           {{NGINX_EVENT_USE}};
    worker_connections  128;
}

error_log         {{LOCO_SVC_VAR}}/error.log info;

http {
    server_tokens off;
    include       {{LOCO_SVC_CFG}}/mime.types;
    charset       utf-8;

    access_log    {{LOCO_SVC_VAR}}/access.log  combined;

    server {
       server_name   {{HTTPD_DOMAIN}}
       listen        {{LOCALHOST}}:{{HTTPD_PORT}}

        error_page    500 502 503 504  /50x.html;

        root {{HTTPD_ROOT}};
        include {{LOCO_PRJ}}/nginx/common.conf;

        location /esr {
            auth_basic           "CiviCRM Extended Security Release";
            auth_basic_user_file {{LOCO_PRJ}}/app/config/esr.htpasswd;
            try_files $uri /{{HTTPD_MAIN}}.php$is_args$args;
        }

        location / {
            # try to serve file directly, fallback to app.php
            try_files $uri /{{HTTPD_MAIN}}.php$is_args$args;
        }

        location ~ ^/app(_dev)?\.php(/|$) {
            alias {{HTTPD_ROOT}}/;
            fastcgi_pass {{LOCALHOST}}:{{PHPFPM_PORT}};
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include {{LOCO_SVC_CFG}}/fastcgi_params;
            # When you are using symlinks to link the document root to the
            # current version of your application, you should pass the real
            # application path instead of the path to the symlink to PHP
            # FPM.
            # Otherwise, PHP's OPcache may not properly detect changes to
            # your PHP files (see https://github.com/zendtech/ZendOptimizerPlus/issues/126
            # for more information).
            fastcgi_param  SCRIPT_FILENAME  $realpath_root$fastcgi_script_name;
            fastcgi_param DOCUMENT_ROOT $realpath_root;
            # Prevents URIs that include the front controller. This will 404:
            # http://domain.tld/app.php/some-path
            # Remove the internal directive to allow URIs like this
            internal;
        }

        location = / {
          rewrite ^ https://civicrm.org/download redirect;
        }

    }


}