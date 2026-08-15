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

## Enchaînement : rien ne part sans contrôles verts

`ci.yml` et `codeql.yml` sont **appelables** (`workflow_call`). Les workflows de
déploiement les lancent d'abord, et le job d'envoi les attend :

```yaml
jobs:
  ci:      { uses: ./.github/workflows/ci.yml }
  codeql:  { uses: ./.github/workflows/codeql.yml }
  deploy:
    needs: [ci, codeql]      # ← rien n'est envoyé si l'un des deux échoue
```

Un `needs` non satisfait ne fait pas échouer le job de déploiement : il le
**saute**. Aucune release partielle n'est donc possible — le serveur n'est jamais
touché tant que tout n'est pas au vert.

> Les deux workflows n'ont **pas** de déclencheur `push` : ils tourneraient deux
> fois sur les branches déployées. Ils se déclenchent sur les *pull requests*
> (garde-fou de merge) et sont appelés par les déploiements (garde-fou de mise en
> ligne).

## Déploiement

| Workflow | Déclencheur | Cible |
| --- | --- | --- |
| `deploy-preprod.yml` | poussée sur `develop`, ou manuel | `~/preprod-lcds` |
| `deploy-prod.yml` | poussée d'un **tag** | `~/prod-lcds` |

Les deux appellent le workflow réutilisable `deploy.yml` (build → sauvegarde →
rsync → recréation des liens vers `shared/`), calqué sur celui de
`~/Sites/code-cookie`. Procédure complète, arborescence serveur, secrets et
rollback : **[`deploiement.md`](deploiement.md)**.

## Bloquer les merges : protection de branche

Le chaînage ci-dessus protège les **déploiements**. Empêcher un merge tant que
les contrôles ne sont pas verts se règle côté GitHub, **pas dans le YAML** :
*Settings > Branches > Add branch ruleset* (ou *Branch protection rules*).

À activer sur `main` **et** `develop` :

- **Require a pull request before merging** ;
- **Require status checks to pass before merging**, en cochant :
  - `Qualité PHP (Pint + PHPCS + PHPStan)`
  - `Tests (Pest)`
  - `CVE des dépendances`
  - `Détection de secrets (gitleaks)`
  - `Build front (webpack)`
  - `Analyse CodeQL`
- **Require branches to be up to date before merging** — sinon une PR verte peut
  casser `develop` si une autre PR a été mergée entre-temps.

En ligne de commande, pour les deux branches :

```bash
for BRANCH in main develop; do
  gh api -X PUT "repos/Rapkalin/LCDS/branches/$BRANCH/protection" \
    -H "Accept: application/vnd.github+json" \
    -F "required_status_checks[strict]=true" \
    -f "required_status_checks[contexts][]=Qualité PHP (Pint + PHPCS + PHPStan)" \
    -f "required_status_checks[contexts][]=Tests (Pest)" \
    -f "required_status_checks[contexts][]=CVE des dépendances" \
    -f "required_status_checks[contexts][]=Détection de secrets (gitleaks)" \
    -f "required_status_checks[contexts][]=Build front (webpack)" \
    -f "required_status_checks[contexts][]=Analyse CodeQL" \
    -F "enforce_admins=false" \
    -F "required_pull_request_reviews[required_approving_review_count]=0" \
    -F "restrictions=null"
done
```

> Les noms des checks sont ceux des **jobs** (`name:`) de `ci.yml` et
> `codeql.yml`. Les renommer dans le YAML sans mettre à jour la protection de
> branche rend la règle inopérante : GitHub attend un check qui n'arrive jamais,
> la PR reste bloquée. Le plus sûr est de les sélectionner dans l'interface, qui
> ne propose que des noms réellement observés.

**Approbation manuelle avant la production** : créer l'environnement `production`
dans *Settings > Environments* et y ajouter un *required reviewer*. Le job de
déploiement s'arrêtera alors en attente de validation — voir
[`deploiement.md`](deploiement.md).

## Ce qui n'a pas été repris de game-france

Le pipeline GitLab de référence contient des jobs liés à l'infrastructure privée
Steamulo : `helm_update`, `PhpFPMComposerInstall`, `Kube.gitlab-ci.yml`,
`$REGISTRY_PROXY`, templates `gitlab-ci/gitlab-ci-templates`. Ils dépendent d'un
GitLab et d'un cluster internes et **n'ont pas d'équivalent ici**.

Le workflow gère déjà les points sensibles : `npm run build` rejoué (le `dist/`
du thème n'est pas versionné), `.env` et médias exclus du transfert, purge des
caches. Le rechargement de PHP-FPM (OPcache) est en commentaire à la fin de
`deploy.yml`, à activer avec la commande de l'hébergeur.
