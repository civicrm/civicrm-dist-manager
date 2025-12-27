{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2025-12-27 22:46 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/da2e36e5eed9cf6d0da0263cbcfb4db0d235b1c6.tar.gz";
  sha256 = "0sg5s62iv33a7y89mg5cq4n1q2dry86cmsa3ialwgvghphhx29j6";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
