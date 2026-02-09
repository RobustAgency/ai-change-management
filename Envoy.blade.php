@servers(['staging' => 'dev@144.126.254.193', 'production' => 'app-user@3.227.157.199'])

@setup
    $env = isset($env) ? $env : 'staging';
    $repository = ($env == 'production') ? 'git@github.com:RobustAgency/ai-change-management' : 'git@github.com-repo-12:RobustAgency/ai-change-management';
    $branch = $env == 'production' ? 'main' : 'staging';
    $app_dir = $env == 'production' ? '/var/www/app' : '/var/www/ai-change-management';
    $release = date('Y_m_d_H_i');
    $releases_dir = $app_dir . '/releases';
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy', ['on' => $env])
    clone_repository
    run_composer
    writeable
    maintenance_down
    update_symlinks
    migrate
    optimize
    restart_queues
    maintenance_up
    cleanup_old_releases
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir -p {{ $releases_dir }}
    git clone --depth 1 --branch {{ $branch }} {{ $repository }} {{ $new_release_dir }}
@endtask

@task('writeable')
    echo 'make bootstrap/cache writeable ...'
    cd {{ $new_release_dir }}
    chgrp -R www-data bootstrap/cache
    chmod -R g+w bootstrap/cache
@endtask

@task('migrate')
    echo "migrating database ..."
    cd {{ $new_release_dir }}
    php artisan migrate --force -q
@endtask

@task('run_composer')
    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o {{ $env == 'production' ? '--no-dev' : '' }}
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current

    echo 'Symlinking storage to public folder'
    cd {{ $new_release_dir }} && php artisan storage:link
@endtask

@task('maintenance_down')
    echo "Putting application in maintenance mode..."
    if [ -d {{ $app_dir }}/current ]; then
        cd {{ $app_dir }}/current && php artisan down --secret="bypass-token"
    fi
@endtask

@task('optimize')
    echo "Optimizing application..."
    cd {{ $new_release_dir }}
    php artisan optimize
@endtask

@task('restart_queues')
    cd {{ $new_release_dir }}
    php artisan queue:restart
@endtask

@task('maintenance_up')
    echo "Bringing application back up..."
    cd {{ $new_release_dir }}
    php artisan up
@endtask

@task('cleanup_old_releases')
    echo "Cleaning up old releases..."
    cd {{ $releases_dir }}
    ls -dt */ | tail -n +6 | xargs -d '\n' rm -rf
@endtask