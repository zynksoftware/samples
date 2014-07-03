These scripts are intended purely as a starting point. Zynk Software Limited is not responsible for how these scripts are used

Setup / Install.

1. Copy 'catalog' and 'admin' 

2 - Install module
Extensions > Modules > Zynk Integration > Install

3 - Set module permissions in admin area:

System > Users > User Groups > Top Administrator
Access Permission: module/zynk
Modify Permission: module/zynk

4 - Enable and Configure module
Extensions > Modules > Zynk Integration > Edit
Set 'Status' to Enabled.

5 - In case of tokens issue:

System > Settings > Server
Ignore Tokens on these pages: module/zynk

URL's:
Download: http://www.domain.com/index.php?route=module/zynk/download
Download Products: http://www.domain.com/index.php?route=module/zynk/download&products
Download Products (Date filter): http://domain.com/index.php?route=module/zynk/download&products&modified_date=YYYY-MM-DD HH:MM:SS
Notify:   http://www.domain.com/index.php?route=module/zynk/download
Upload:   http://www.domain.com/index.php?route=module/zynk/upload


Debug:

1 - Download specific order, regardless of status:
    http://www.domain.com/index.php?route=module/zynk/download&orderid=[order_id]

2 - Read file directly on upload:
    Place XML file in root of site:
    http://www.domain.com/index.php?route=module/zynk/upload&file=[filename].[ext]
