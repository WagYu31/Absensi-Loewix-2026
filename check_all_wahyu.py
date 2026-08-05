import os, re

fpath = "/Users/wagyua5/Documents/Absensi/db_u836263092_ssll_20260721_013007_mysql_data.sql 2"
if not os.path.exists(fpath):
    for f in os.listdir("/Users/wagyua5/Documents/Absensi"):
        if "20260721" in f:
            fpath = os.path.join("/Users/wagyua5/Documents/Absensi", f)
            break

with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
    text = f.read()

# Check table denda, rincian_gaji, etc for Wahyu Utomo / 577 / 16577
for tbl in ["denda", "rincian_gaji", "absen_manual", "absen"]:
    match = re.search(r"INSERT INTO `" + tbl + r"` VALUES\s*(.*?);", text, re.DOTALL)
    if match:
        rows = match.group(1).split("\n")
        w_rows = [r for r in rows if "'577'" in r or "'16577'" in r or "Wahyu Utomo" in r]
        print(f"Table `{tbl}` has {len(w_rows)} matching rows for Wahyu Utomo")
        if w_rows and tbl != "absen":
            for r in w_rows[:5]:
                print("  ", r[:120])
