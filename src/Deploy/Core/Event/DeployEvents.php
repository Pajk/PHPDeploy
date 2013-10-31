<?php

namespace Deploy\Core\Event;

final class DeployEvents
{
    const PRE_INIT = 'deploy.pre_init';
    const INIT = 'deploy.init';
    const POST_INIT = 'deploy.post_init';

    const PRE_DEPLOY = 'deploy.pre_deploy';
    const DEPLOY = 'deploy.deploy';
    const POST_DEPLOY = 'deploy.post_deploy';

    const PRE_ROLLBACK = 'deploy.pre_rollback';
    const ROLLBACK = 'deploy.rollback';
    const POST_ROLLBACK = 'deploy.post_rollback';

    const FAILED = 'deploy.failed';
}