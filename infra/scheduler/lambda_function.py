import boto3
import os
from botocore.exceptions import ClientError

ecs = boto3.client('ecs')
rds = boto3.client('rds')

CLUSTER     = os.environ['ECS_CLUSTER']
SERVICE     = os.environ['ECS_SERVICE']
DB_INSTANCE = os.environ['RDS_INSTANCE_ID']


def handler(event, context):
    """
    EventBridge Scheduler から呼び出されるハンドラー。

    event['action']:
      'stop'      - 平日 01:00 JST: ECS停止 + RDS停止
      'start_rds' - 平日 09:00 JST: RDS起動
      'start_ecs' - 平日 09:15 JST: ECS起動（RDS起動から15分後）
    """
    action = event.get('action')

    if action == 'stop':
        ecs.update_service(cluster=CLUSTER, service=SERVICE, desiredCount=0)
        _rds_action('stop_db_instance')
        print(f'[stop] ECS desiredCount=0, RDS stop initiated: {DB_INSTANCE}')

    elif action == 'start_rds':
        _rds_action('start_db_instance')
        print(f'[start_rds] RDS start initiated: {DB_INSTANCE}')

    elif action == 'start_ecs':
        ecs.update_service(cluster=CLUSTER, service=SERVICE, desiredCount=1)
        print(f'[start_ecs] ECS desiredCount=1: {SERVICE}')

    else:
        raise ValueError(f'Unknown action: {action}')


def _rds_action(method):
    try:
        getattr(rds, method)(DBInstanceIdentifier=DB_INSTANCE)
    except ClientError as e:
        if e.response['Error']['Code'] != 'InvalidDBInstanceState':
            raise
        print(f'RDS already in target state, skipping {method}')
