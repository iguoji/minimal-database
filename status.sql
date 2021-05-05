# Threads_connected 打开的连接数  
# Threads_running   激活的连接数，这个数值一般远低于connected数值  
SHOW STATUS LIKE 'Threads%';


SHOW VARIABLES LIKE '%timeout%';

# 最大连接数
SHOW VARIABLES LIKE '%max_connections%';  