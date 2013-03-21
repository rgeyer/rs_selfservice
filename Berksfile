cookbook "yum"

cookbook "zf2_vagrant_aio",
  git: "git://github.com/rgeyer-rs-cookbooks/zf2_vagrant_aio.git"

group :release do
  cookbook "rsss",
    path: "cookbooks/rsss"

  cookbook "rightscale",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "d5cb3edf0c82d1cd0a8ced3ff169a692fb3d9488",
    rel: "cookbooks/rightscale"
end