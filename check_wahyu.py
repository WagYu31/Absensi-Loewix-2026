import os, re

fpath = "/Users/wagyua5/Documents/Absensi/db_u836263092_ssll_20260721_013007_mysql_data.sql 2"
if not os.path.exists(fpath):
    for f in os.listdir("/Users/wagyua5/Documents/Absensi"):
        if "20260721" in f:
            fpath = os.path.join("/Users/wagyua5/Documents/Absensi", f)
            break

with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
    text = f.read()

# search all rows for Wahyu Utomo in absen
match_absen = re.search(r"INSERT INTO `absen` VALUES\s*(.*?);", text, re.DOTALL)
if match_absen:
    content = match_absen.group(1)
    rows = content.split("\n")
    wahyu_rows = [r for r in rows if "Wahyu Utomo" in r or "'577'" in r or "'16577'" in r]
    print(f"Total Wahyu Utomo rows in absen: {len(wahyu_rows)}")
    for r in wahyu_rows[-10:]:
        print(r[:140])

match_manual = re.search(r"INSERT INTO `absen_manual` VALUES\s*(.*?);", text, re.DOTALL)
if match_manual:
    content = match_manual.group(1)
    rows = content.split("\n")
    wahyu_rows = [r for r in rows if "Wahyu Utomo" in r or "'577'" in r or "'16577'" in r]
    print(f"\nTotal Wahyu Utomo rows in absen_manual: {len(wahyu_rows)}")
    for r in wahyu_rows[-10:]:
        print(r[:140])
