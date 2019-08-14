#!/bin/bash



cluster_name=`echo "$1" | sed -r 's/^cop\.([^_\.]+)?_owt\.([^_\.]+)?_pdl\.([^_\.]+)?_cluster\.([^_\.]+)?.*/\4/'`
servicegroup_name=`echo "$1" | sed -r 's/^cop\.([^_\.]+)?_owt\.([^_\.]+)?_pdl\.([^_\.]+)?(.*)?_servicegroup\.([^_\.]+)?.*/\5/'`
service_name=`echo "$1" | sed -r 's/^cop\.([^_\.]+)?_owt\.([^_\.]+)?_pdl\.([^_\.]+)?(.*)?_service\.([^_\.]+)?.*/\5/'`
job_name=`echo "$1" | sed -r 's/^cop\.([^_\.]+)?_owt\.([^_\.]+)?_pdl\.([^_\.]+)?(.*)?_job\.([^_\.]+)?.*/\5/'`



basedir=`dirname $0`
cd $basedir     || exit 1
mkdir -p release/ || exit 1

for file in `ls -a $basedir`
do
    if [ $file == "." ] ||  [ $file == ".." ] || [ $file == "release" ] || [ $file == ".git" ];then
        continue;
    fi
    cp -r $file release/ || exit 1
done;

project_path=${1}
echo "project_path:" + ${project_path}

#cluster name
cluster=${cluster_name##*.}
echo "cluster:" + ${cluster}


