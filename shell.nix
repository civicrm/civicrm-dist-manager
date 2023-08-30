{ pkgs ? import <nixpkgs> {} }:

let

  buildkit = (import ./nix/buildkit.nix) { inherit pkgs; };
  ## TIP: To update buildkit, run ./nix/buildkit-update.sh

in

  pkgs.mkShell {
    nativeBuildInputs = buildkit.profiles.base ++ [

      buildkit.pkgs.php74
      buildkit.pins.v2305.nginx
      buildkit.pkgs.loco
      (buildkit.funcs.fetchPhar {
        name = "composer";
        url = "https://github.com/composer/composer/releases/download/2.2.21/composer.phar";
        sha256 = "5211584ad39af26704da9f6209bc5d8104a2d576e80ce9c7ed8368ddd779d0af";
      })
      buildkit.pkgs.phpunit8

      pkgs.bash-completion
    ];
    shellHook = ''
      source ${pkgs.bash-completion}/etc/profile.d/bash_completion.sh
    '';
  }
