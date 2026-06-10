import os
import sys
from PIL import Image

def convert_to_webp(directory):
    count = 0
    errors = 0
    for root, dirs, files in os.walk(directory):
        if any(x in root for x in [".git", "node_modules", "vendor"]):
            continue
            
        for file in files:
            if file.lower().endswith(('.png', '.jpg', '.jpeg', '.gif')):
                input_path = os.path.join(root, file)
                # Skip already converted or if it is already a webp (shouldn't happen with extension check)
                
                # New path with .webp extension
                output_path = os.path.splitext(input_path)[0] + ".webp"
                
                try:
                    img = Image.open(input_path)
                    
                    # Convert to RGB if needed (GIF/PNG with transparency can be converted)
                    # For WebP, many cases work fine with RGBA
                    
                    img.save(output_path, "WEBP", quality=80)
                    print(f"✅ Converted: {input_path} -> {output_path}")
                    count += 1
                except Exception as e:
                    print(f"❌ Error converting {input_path}: {e}")
                    errors += 1
                    
    print(f"\nSummary:")
    print(f"Converted: {count}")
    print(f"Errors: {errors}")

if __name__ == "__main__":
    target_dir = sys.argv[1] if len(sys.argv) > 1 else "."
    convert_to_webp(target_dir)
