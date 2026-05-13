import zipfile
import re
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r"d:\thesmartr\product_upload_food_beverage_seeded.xlsx"
z = zipfile.ZipFile(path)
xml = z.read("xl/worksheets/sheet1.xml").decode("utf-8", "replace")
m = re.search(r'<dimension[^>]+ref="([^"]+)"', xml)
print("dimension:", m.group(1) if m else None)
# count row tags r="N"
rows = re.findall(r'<row[^>]*r="(\d+)"', xml)
print("row tags sample:", rows[:25], "... total tagged rows", len(rows))
print("last row:", max(int(x) for x in rows) if rows else None)
print("---snippet---")
print(xml[:4000])
