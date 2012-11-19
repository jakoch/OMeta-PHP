#!/bin/bash
output=$(php ./php-cs-fixer/php-cs-fixer.phar fix -v --level=all --dry-run ../framework);
if [[ $output ]]; then
    while read -r line;
    do echo -e "\e[00;31m$line\e[00m";
    done <<< "$output";
    false;
fi;
