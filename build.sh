#!/usr/bin/env bash

echo -e "\e[92m######################################################################"
echo -e "\e[92m#                                                                    #"
echo -e "\e[92m#                      Start BaseLinker ShopsAPI Builder             #"
echo -e "\e[92m#                                                                    #"
echo -e "\e[92m######################################################################"

echo -e "Release"
echo -e "\e[39m "
echo -e "\e[39m======================================================================"
echo -e "\e[39m "
echo -e "Step 1 of 6 \e[33mRemove old release\e[39m"
# Remove old release
rm -rf CrehlerBaseLinkerShopsApi CrehlerBaseLinkerShopsApi-*.zip
echo -e "\e[32mOK"

echo -e "\e[39m "
echo -e "\e[39m======================================================================"
echo -e "\e[39m "
echo -e "Step 2 of 6 \e[33mCopy files\e[39m"
rsync -av --progress . CrehlerBaseLinkerShopsApi --exclude CrehlerBaseLinkerShopsApi
echo -e "\e[32mOK"


echo -e "\e[39m "
echo -e "\e[39m======================================================================"
echo -e "\e[39m "
echo -e "Step 3 of 6 \e[33mGo to directory\e[39m"
cd CrehlerBaseLinkerShopsApi
echo -e "\e[32mOK"

echo -e "\e[39m "
echo -e "\e[39m======================================================================"
echo -e "\e[39m "
echo -e "Step 4 of 6 \e[33mDeleting unnecessary files\e[39m"
cd ..
( find ./CrehlerBaseLinkerShopsApi -type d -name ".git" && find ./CrehlerBaseLinkerShopsApi -name ".gitignore" && find ./CrehlerBaseLinkerShopsApi -name "yarn.lock" && find ./CrehlerBaseLinkerShopsApi -name ".php_cs.dist" &&  find ./CrehlerBaseLinkerShopsApi -name ".gitmodules" && find ./CrehlerBaseLinkerShopsApi -name "build.sh" ) | xargs rm -r
cd CrehlerBaseLinkerShopsApi/src/Resources
# rm -rf administration
cd ../../../
echo -e "\e[32mOK"


echo -e "\e[39m "
echo -e "\e[39m======================================================================"
echo -e "\e[39m "
echo -e "Step 5 of 6 \e[33mCreate ZIP\e[39m"
zip -rq CrehlerBaseLinkerShopsApi-master.zip CrehlerBaseLinkerShopsApi
echo -e "\e[32mOK"

echo -e "\e[39m "
echo -e "\e[39m======================================================================"
echo -e "\e[39m "
echo -e "Step 6 of 6 \e[33mClear build directory\e[39m"
rm -rf CrehlerBaseLinkerShopsApi
echo -e "\e[32mOK"


echo -e "\e[92m######################################################################"
echo -e "\e[92m#                                                                    #"
echo -e "\e[92m#                        Build Complete                              #"
echo -e "\e[92m#                                                                    #"
echo -e "\e[92m######################################################################"
echo -e "\e[39m "
echo "   _____          _     _           ";
echo "  / ____|        | |   | |          ";
echo " | |     _ __ ___| |__ | | ___ _ __ ";
echo " | |    | '__/ _ \ '_ \| |/ _ \ '__|";
echo " | |____| | |  __/ | | | |  __/ |   ";
echo "  \_____|_|  \___|_| |_|_|\___|_|   ";
echo "                                    ";
echo "                                    ";
