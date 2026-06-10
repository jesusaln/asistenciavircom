import os
from PIL import Image

def remove_bg():
    mascot_path = "/home/vircom/.gemini/antigravity/brain/9b31af55-3bfa-4f4e-a470-c5fc16e13780/tecnico_mascot_1774560256369.png"
    out_path = "/home/vircom/.gemini/antigravity/scratch/climasdeldesierto/public/images/tecnico_bgless.png"
    
    img = Image.open(mascot_path).convert("RGBA")
    datas = img.getdata()
    new_data = []
    
    # tolerance for white
    tol = 240
    for item in datas:
        # replace white or near white with transparent
        if item[0] > tol and item[1] > tol and item[2] > tol:
            # We want an anti-aliasing effect so hard edges don't look too bad,
            # but for a quick mascot, just thresholding is fine for a white background printing.
            new_data.append((255, 255, 255, 0))
        else:
            new_data.append(item)
            
    img.putdata(new_data)
    
    bbox = img.getbbox()
    if bbox:
        img = img.crop(bbox)
        
    img.save(out_path, "PNG")
    print(f"Saved mascot to {out_path}")

remove_bg()
