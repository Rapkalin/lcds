# CI/CD

La CI tourne sur **GitHub Actions**. Deux workflows :

## `.github/workflows/ci.yml`

Déclenché sur `push` et `pull_request` vers `main` et `develop`. Une nouvelle
poussée annule le run précédent encore en cours (`concurrency`).

| Job | Ce qu'il fait |
| --- | --- |
| **php-quality** | `composer check` — Pint (style) + PHPCS/Slevomat (types natifs) + PHPStan (analyse statique). |
| **php-tests** | `composer test` — suite Pest. |
| **dependency-cve** | `composer audit --locked --abandoned=ignore` — audite `composer.lock` contre la base d'avis de sécurité Packagist/GitHub, sans installer `vendor/`. Un paquet abandonné (≠ vulnérable) ne fait pas échouer le job. |
| **secret-scan** | gitleaks sur l'**historique complet** (`fetch-depth: 0`), pas seulement l'état courant. |
| **build-front** | Node 20, `npm ci` puis `npm run build`. `dist/` n'étant pas versionné, ce job vérifie que la compilation passe toujours ; il ne publie aucun artefact. |

`composer.lock` étant committé, l'installation est **déterministe**.

## `.github/workflows/codeql.yml`

SAST CodeQL, sur push/PR et une passe hebdomadaire (une règle CodeQL publiée
après le dernier commit doit pouvoir remonter un problème sur du code figé).

> ⚠️ **CodeQL ne supporte pas PHP.** Seul le JavaScript du thème est analysé. La
> couverture sécurité du PHP repose sur **PHPStan** et sur l'**audit CVE**.
>
> ⚠️ Sur un dépôt **privé**, CodeQL exige **GitHub Advanced Security** (payant) et
> ce workflow échouera. Si LCDS est privé sans GHAS : supprimer
> `codeql.yml` — les autres garde-fous restent en place.

## Déploiement

| Workflow | Déclencheur | Cible |
| --- | --- | --- |
| `deploy-preprod.yml` | poussée sur `develop`, ou manuel | `~/preprod-lcds` |
| `deploy-prod.yml` | poussée d'un **tag** | `~/prod-lcds` |

Les deux appellent le workflow réutilisable `deploy.yml` (build → sauvegarde →
rsync → recréation des liens vers `shared/`), calqué sur celui de
`~/Sites/code-cookie`. Procédure complète, arborescence serveur, secrets et
rollback : **[`deploiement.md`](deploiement.md)**.

## Ce qui n'a pas été repris de game-france

Le pipeline GitLab de référence contient des jobs liés à l'infrastructure privée
Steamulo : `helm_update`, `PhpFPMComposerInstall`, `Kube.gitlab-ci.yml`,
`$REGISTRY_PROXY`, templates `gitlab-ci/gitlab-ci-templates`. Ils dépendent d'un
GitLab et d'un cluster internes et **n'ont pas d'équivalent ici**.

Le workflow gère déjà les points sensibles : `npm run build` rejoué (le `dist/`
du thème n'est pas versionné), `.env` et médias exclus du transfert, purge des
caches. Le rechargement de PHP-FPM (OPcache) est en commentaire à la fin de
`deploy.yml`, à activer avec la commande de l'hébergeur.
