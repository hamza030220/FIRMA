#!/usr/bin/env python3
"""
Seed fake data for the forum admin statistics only.

This script uses Symfony's Doctrine SQL command, so it does not require a
Python database driver.

It creates:
- forum categories
- forum users
- forum posts
- forum comments
- post reactions
- moderation alerts
- forum bookmarks
- pinned posts

Usage:
    python scripts/seed_admin_demo_data.py
"""

from __future__ import annotations

import argparse
import json
import subprocess
from datetime import date, datetime, timedelta
from pathlib import Path


PROJECT_DIR = Path(__file__).resolve().parents[1]


def sql_quote(value: object) -> str:
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "1" if value else "0"
    if isinstance(value, (list, dict)):
        return "'" + json.dumps(value, ensure_ascii=False).replace("\\", "\\\\").replace("'", "''") + "'"
    if isinstance(value, (int, float)):
        return str(value)
    if isinstance(value, datetime):
        return "'" + value.strftime("%Y-%m-%d %H:%M:%S") + "'"
    if isinstance(value, date):
        return "'" + value.strftime("%Y-%m-%d") + "'"
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def dt(offset_days: int = 0, hour: int = 10, minute: int = 0) -> datetime:
    now = datetime.now()
    return (now + timedelta(days=offset_days)).replace(hour=hour, minute=minute, second=0, microsecond=0)


def execute_sql(sql: str) -> None:
    command = ["php", "bin/console", "doctrine:query:sql", sql]
    result = subprocess.run(command, cwd=PROJECT_DIR, capture_output=True, text=True)

    if result.returncode != 0:
        raise RuntimeError(
            "SQL execution failed:\n"
            f"Command: {' '.join(command)}\n"
            f"STDOUT:\n{result.stdout}\n"
            f"STDERR:\n{result.stderr}"
        )


def insert_rows(table: str, columns: list[str], rows: list[list[object]]) -> None:
    if not rows:
        return

    values_sql = ", ".join(
        "(" + ", ".join(sql_quote(value) for value in row) + ")"
        for row in rows
    )
    execute_sql(f"INSERT INTO {table} ({', '.join(columns)}) VALUES {values_sql}")


def cleanup_demo_data() -> None:
    statements = [
        "DELETE a FROM forum_moderation_alert a WHERE a.utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local') OR a.commentaire_id IN (SELECT id FROM commentaire WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local'))",
        "DELETE FROM post_reaction WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local') OR post_id IN (SELECT id FROM post WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local'))",
        "DELETE FROM forum_post_bookmark WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local') OR post_id IN (SELECT id FROM post WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local'))",
        "DELETE FROM commentaire WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local') OR post_id IN (SELECT id FROM post WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local'))",
        "DELETE FROM post WHERE utilisateur_id IN (SELECT id FROM utilisateurs WHERE email LIKE 'demo-%@firma.local')",
        "DELETE FROM categorie_forum WHERE nom IN ('Irrigation et eau', 'Sol et fertilisation', 'Semis et plantations', 'Protection des cultures', 'Mecanisation agricole', 'Recolte et stockage', 'Commercialisation', 'Logistique et transport', 'Gestion d''exploitation', 'Innovation et technique')",
        "DELETE FROM utilisateurs WHERE email LIKE 'demo-%@firma.local'",
    ]

    for sql in statements:
        execute_sql(sql)


def generate_password_hash(raw_password: str = "Demo123!") -> str:
    code = f"echo password_hash({raw_password!r}, PASSWORD_BCRYPT);"
    result = subprocess.run(["php", "-r", code], cwd=PROJECT_DIR, capture_output=True, text=True)

    if result.returncode != 0:
        raise RuntimeError(
            "Password hash generation failed:\n"
            f"STDOUT:\n{result.stdout}\n"
            f"STDERR:\n{result.stderr}"
        )

    hashed = result.stdout.strip()
    if not hashed:
        raise RuntimeError("Password hash generation returned an empty value.")

    return hashed


def seed_users(run_id: str, password_hash: str) -> list[dict[str, str]]:
    specs = [
        ("Amina", "Ben Salah", "client"),
        ("Youssef", "Trabelsi", "client"),
        ("Nour", "Khadhraoui", "client"),
        ("Malek", "Ferjani", "client"),
        ("Marwa", "Haddad", "client"),
        ("Omar", "Chaabane", "client"),
        ("Hedi", "Sliti", "technicien"),
        ("Sarra", "Mansouri", "technicien"),
    ]

    rows: list[list[object]] = []
    users: list[dict[str, str]] = []
    for index, (prenom, nom, type_user) in enumerate(specs, start=1):
        email = f"demo-{run_id}-u{index}@firma.local"
        users.append({"prenom": prenom, "nom": nom, "email": email, "type_user": type_user})
        rows.append(
            [
                nom,
                prenom,
                email,
                f"+216 20 55 {index:02d} {index:02d}",
                f"Forum street {index}",
                "Tunis" if index % 2 else "Sfax",
                type_user,
                password_hash,
                dt(-20 + index),
            ]
        )

    insert_rows(
        "utilisateurs",
        ["nom", "prenom", "email", "telephone", "adresse", "ville", "type_user", "mot_de_passe", "date_creation"],
        rows,
    )
    return users


def seed_categories(run_id: str) -> list[str]:
    names = [
        "Irrigation et eau",
        "Sol et fertilisation",
        "Semis et plantations",
        "Protection des cultures",
        "Mecanisation agricole",
        "Recolte et stockage",
        "Commercialisation",
        "Logistique et transport",
        "Gestion d'exploitation",
        "Innovation et technique",
    ]

    insert_rows(
        "categorie_forum",
        ["nom"],
        [[name] for name in names],
    )
    return names


def seed_posts(run_id: str, users: list[dict[str, str]], categories: list[str]) -> list[str]:
    specs = [
        ("Comment optimiser l'irrigation sur petite surface ?", "J'exploite une petite parcelle et je voudrais reduire la consommation d'eau sans perdre en rendement. Quelles methodes vous ont vraiment aides ?", categories[0]),
        ("Quel amendement utilisez-vous pour un sol pauvre ?", "Mon sol est assez fatigue apres plusieurs saisons. Je cherche des retours sur les amendements organiques ou mineraux les plus efficaces.", categories[1]),
        ("Quelle periode conseillez-vous pour les semis de printemps ?", "Je prepare les semis et j'aimerais suivre une periode fiable selon vos experiences sur le terrain.", categories[2]),
        ("Comment prevenez-vous les maladies foliaires ?", "Cette annee j'ai eu pas mal de problemes sur les feuilles. Quels traitements ou bonnes pratiques vous conseillez ?", categories[3]),
        ("Tracteur d'occasion ou neuf pour une exploitation moyenne ?", "Je pense changer de tracteur. Est-ce qu'un modele d'occasion bien entretenu vaut le coup ou il vaut mieux viser du neuf ?", categories[4]),
        ("Techniques pour mieux conserver les recoltes", "Je cherche des conseils concrets pour mieux stocker les produits apres la recolte sans perte de qualite.", categories[5]),
        ("Fixer un bon prix de vente au marche", "Comment calculez-vous vos prix pour rester competitifs tout en couvrant les couts de production ?", categories[6]),
        ("Organisation des livraisons entre plusieurs parcelles", "Je dois faire circuler les recoltes entre plusieurs sites. Quelle methode de transport vous semble la plus pratique ?", categories[7]),
        ("Comment mieux gerer les couts de l'exploitation ?", "J'aimerais avoir une vision plus claire des depenses mensuelles. Vous utilisez quels outils ou quelles habitudes ?", categories[8]),
        ("Nouveaux outils agricoles utiles cette saison", "Je veux decouvrir les outils ou solutions techniques qui font vraiment gagner du temps dans le travail quotidien.", categories[9]),
        ("Retour d'experience sur la rotation des cultures", "J'ai commence une rotation plus variee cette saison et j'aimerais comparer mes resultats avec les votres.", categories[2]),
        ("Quel engrais pour relancer un terrain fatigue ?", "Je veux corriger un terrain qui produit moins bien. Quels engrais ou apports naturels avez-vous testés ?", categories[1]),
        ("Irrigation goutte a goutte: rentable ou pas ?", "J'hésite à investir dans le goutte à goutte. Est-ce que l'economie d'eau compense vraiment le cout d'installation ?", categories[0]),
        ("Comment preparer la campagne de recolte ?", "La campagne arrive vite et je voudrais une methode simple pour anticiper la main-d'oeuvre, le stockage et le transport.", categories[5]),
        ("Quel semoir pour une petite exploitation ?", "Je cherche un semoir simple et robuste, pas trop cher, pour une petite exploitation de taille moyenne.", categories[4]),
        ("Comment mieux proteger les cultures par forte chaleur ?", "Avec les fortes temperatures, certaines cultures souffrent. Quels gestes ou protections vous utilisez ?", categories[3]),
        ("Astuces pour vendre plus facilement sa production", "Je cherche des idees pour ecouler la production plus vite, que ce soit au marche, en direct ou via un autre circuit.", categories[6]),
        ("Comment gagner du temps sur le terrain ?", "Je voudrais organiser les journees de travail pour limiter les deplacements inutiles et rester plus efficace.", categories[8]),
    ]

    titles: list[str] = []
    rows: list[list[object]] = []
    for index, (title, content, category) in enumerate(specs, start=1):
        author = users[index % len(users)]["email"]
        full_title = title
        titles.append(full_title)
        post_date = dt(-6 + ((index - 1) % 7), hour=8 + (index % 8))
        pinned = 1 if index in (1, 4, 9, 13, 17) else 0
        rows.append(
            [
                f"(SELECT id FROM utilisateurs WHERE email = {sql_quote(author)} LIMIT 1)",
                full_title,
                content,
                category,
                post_date,
                "actif",
                pinned,
            ]
        )

    for row in rows:
        execute_sql(
            "INSERT INTO post (utilisateur_id, titre, contenu, categorie, date_creation, statut, is_pinned) "
            f"SELECT {row[0]}, {sql_quote(row[1])}, {sql_quote(row[2])}, {sql_quote(row[3])}, {sql_quote(row[4])}, {sql_quote(row[5])}, {sql_quote(row[6])}"
        )

    return titles


def seed_comments_and_reactions(run_id: str, users: list[dict[str, str]], post_titles: list[str]) -> None:
    comment_texts = [
        "Merci pour ce partage, c'est tres utile.",
        "Je suis d'accord avec cette methode.",
        "Peux-tu donner plus de details ?",
        "Je vais essayer cette approche.",
        "C'est vraiment interessant.",
        "J'ai obtenu un resultat similaire.",
        "Bonne idee pour l'exploitation.",
        "Je recommande aussi cette solution.",
    ]
    image_paths = [
        'uploads/commentaires/comment-047fae771c55b4ba.png',
        'uploads/commentaires/comment-32b1d603e52ece45.png',
        'uploads/commentaires/comment-3fa578d40f717e9a.png',
        'uploads/commentaires/comment-4a78a45c7ec1be29.jpg',
        'uploads/commentaires/comment-62d1f866a92a7bb9.png',
        'uploads/commentaires/comment-7770a735bd517e73.png',
        'uploads/commentaires/comment-7a78c4412b2a53bb.png',
        'uploads/commentaires/comment-7b30dddc17d9d090.png',
    ]

    reaction_types = ["like", "solidaire", "like", "triste", "encolere", "dislike"]

    for index, title in enumerate(post_titles, start=1):
        comment_count = 6 if index % 3 else 5
        for c_index in range(comment_count):
            author = users[(index + c_index) % len(users)]["email"]
            content = comment_texts[(index + c_index) % len(comment_texts)] + f" #{index}-{c_index + 1}"
            comment_date = dt(-5 + ((index + c_index) % 7), hour=11 + c_index)
            image_path = None
            if c_index == 0 or (index % 2 == 0 and c_index == 2):
                image_path = image_paths[(index + c_index) % len(image_paths)]

            execute_sql(
                "INSERT INTO commentaire (post_id, utilisateur_id, contenu, date_creation, image_path) "
                f"SELECT p.id, u.id, {sql_quote(content)}, {sql_quote(comment_date)}, {sql_quote(image_path)} "
                f"FROM post p, utilisateurs u WHERE p.titre = {sql_quote(title)} AND u.email = {sql_quote(author)} LIMIT 1"
            )

        reaction_count = 4 if index % 2 else 5
        for r_index in range(reaction_count):
            reactor = users[(index + r_index + 2) % len(users)]["email"]
            reaction_type = reaction_types[(index + r_index) % len(reaction_types)]
            reaction_date = dt(-4 + ((index + r_index) % 7), hour=14 + r_index)
            execute_sql(
                "INSERT INTO post_reaction (post_id, utilisateur_id, type, date_creation) "
                f"SELECT p.id, u.id, {sql_quote(reaction_type)}, {sql_quote(reaction_date)} "
                f"FROM post p, utilisateurs u WHERE p.titre = {sql_quote(title)} AND u.email = {sql_quote(reactor)} LIMIT 1"
            )


def seed_moderation_alerts(run_id: str, users: list[dict[str, str]], post_titles: list[str]) -> None:
    alerts = [
        ("putain", "p*****", ["putain"], 0, 0),
        ("connard", "c******", ["connard"], 2, 1),
        ("va te faire foutre", "v* t* f**** f*****", ["va te faire foutre"], 4, 2),
        ("merde", "m****", ["merde"], 6, 1),
        ("fuck", "f***", ["fuck"], 8, 0),
        ("shit", "s***", ["shit"], 10, 1),
    ]

    for index, (original, masked, words, post_index, comment_offset) in enumerate(alerts, start=1):
        post_title = post_titles[post_index % len(post_titles)]
        user_email = users[index % len(users)]["email"]
        created_at = dt(-3 + index, hour=9 + index)
        execute_sql(
            "INSERT INTO forum_moderation_alert (commentaire_id, utilisateur_id, original_content, masked_content, matched_words, status, created_at, reviewed_at, note) "
            f"SELECT "
            f"(SELECT c.id FROM commentaire c JOIN post p ON c.post_id = p.id WHERE p.titre = {sql_quote(post_title)} ORDER BY c.id LIMIT 1 OFFSET {comment_offset}), "
            f"(SELECT u.id FROM utilisateurs u WHERE u.email = {sql_quote(user_email)} LIMIT 1), "
            f"{sql_quote(original)}, {sql_quote(masked)}, {sql_quote(words)}, 'pending', {sql_quote(created_at)}, NULL, NULL"
        )


def seed_bookmarks(run_id: str, users: list[dict[str, str]], post_titles: list[str]) -> None:
    main_user = users[0]["email"]
    favorite_titles = post_titles[:4]
    saved_titles = post_titles[4:8]

    for offset, title in enumerate(favorite_titles, start=1):
        created_at = dt(-2 + offset, hour=10 + offset)
        execute_sql(
            "INSERT INTO forum_post_bookmark (post_id, utilisateur_id, bookmark_type, created_at) "
            f"SELECT p.id, u.id, 'favorite', {sql_quote(created_at)} "
            f"FROM post p, utilisateurs u WHERE p.titre = {sql_quote(title)} AND u.email = {sql_quote(main_user)} LIMIT 1"
        )

    for offset, title in enumerate(saved_titles, start=1):
        created_at = dt(-1 + offset, hour=14 + offset)
        execute_sql(
            "INSERT INTO forum_post_bookmark (post_id, utilisateur_id, bookmark_type, created_at) "
            f"SELECT p.id, u.id, 'saved', {sql_quote(created_at)} "
            f"FROM post p, utilisateurs u WHERE p.titre = {sql_quote(title)} AND u.email = {sql_quote(main_user)} LIMIT 1"
        )


def main() -> int:
    parser = argparse.ArgumentParser(description="Seed fake data for forum admin statistics only.")
    parser.add_argument("--run-id", help="Optional run identifier. A timestamp is used if omitted.")
    args = parser.parse_args()

    run_id = args.run_id or datetime.now().strftime("%Y%m%d%H%M%S%f")
    password_hash = generate_password_hash()

    print(f"Seeding forum demo data with run id: {run_id}")

    cleanup_demo_data()
    users = seed_users(run_id, password_hash)
    categories = seed_categories(run_id)
    post_titles = seed_posts(run_id, users, categories)
    seed_comments_and_reactions(run_id, users, post_titles)
    seed_bookmarks(run_id, users, post_titles)
    seed_moderation_alerts(run_id, users, post_titles)

    print("Forum demo data inserted successfully.")
    print("Refresh the admin forum page to see the stats move.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
