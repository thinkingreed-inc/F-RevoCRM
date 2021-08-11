cd /root/
ls
mysql -u root -pdocker -D crmdb -e "select * from vtiger_tab" | sed 's/"/""/g;s/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > ~/vtiger_tab.csv
mysql -u root -pdocker -D frevocrm -e "select * from vtiger_tab" | sed 's/"/""/g;s/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > ~/vtiger_tab.csv
ls
cat vtiger_tab.csv 
mysql -u root -pdocker -D frevocrm -e "select * from vtiger_tab" | sed 's/"/""/g;s/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > ~/vtiger_tab.csv
cat vtiger_tab.csv 
mysql -u root -pdocker -D frevocrm -e "select * from vtiger_tab" | sed 's/"/""/g;s/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > ~/vtiger_tab.csv && nkf --overwrite --oc=UTF-8-BOM ~/vtiger_tab.csv 
mysql -u root -pdocker -D frevocrm -e "select * from vtiger_crmentity" | sed 's/"/""/g;s/\t/","/g;s/^/"/;s/$/"/;s/\n//g' > ~/vtiger_tab.csv && nkf --overwrite --oc=UTF-8-BOM ~/vtiger_tab.csv 
exit
