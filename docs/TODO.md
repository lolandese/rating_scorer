# Rating Scorer Module - TODO

## CI/CD Pipeline

- [ ] **Add `.gitlab-ci.yml` for automated testing and code quality**
  - Run Unit and Functional tests on every commit/merge
  - Test across multiple Drupal versions (D10, D11) and PHP versions
  - Validate compatibility with different Fivestar/Votingapi versions
  - Include PHP standards checks (PHPStan, PHP CodeSniffer)
  - Verify composer dependencies resolve correctly
  - Consider this especially important if publishing to Drupal.org contrib modules
  - For private organization use: valuable for team confidence and preventing bugs
