# Khởi động WordPress Playground Server cho Sunny Grove
$r = (Get-Location).Path

& "$r\.dev\node_modules\.bin\wp-playground-cli.cmd" server `
  --port 9500 `
  --login `
  --wordpress-install-mode do-not-attempt-installing `
  --mount-dir-before-install "$r\.dev\site" /wordpress `
  --mount-dir "$r\themes\sunny-grove" /wordpress/wp-content/themes/sunny-grove `
  --mount-dir "$r\plugins\sunny-grove-core" /wordpress/wp-content/plugins/sunny-grove-core `
  --mount-dir "$r\plugins\sunny-grove-booking" /wordpress/wp-content/plugins/sunny-grove-booking `
  --mount-dir "$r\.dev\mu" /wordpress/wp-content/mu-plugins `
  --mount-dir "$r\.dev\out" /wordpress/wp-content/sg-out

