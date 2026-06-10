from PIL import Image
import os

input_path = "logo.webp"
output_png = "logo.png"
output_ico = "icon.ico"

# Convert WebP to PNG
with Image.open(input_path) as img:
    img.save(output_png, "PNG")
    print(f"Saved {output_png}")

# Convert PNG to ICO with multiple sizes
with Image.open(output_png) as img:
    sizes = [(16, 16), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)]
    img.save(output_ico, format='ICO', sizes=sizes)
    print(f"Saved {output_ico}")
