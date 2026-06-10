from PIL import Image
import math

# Load image
img = Image.open("/home/vircom/.gemini/antigravity/brain/9b31af55-3bfa-4f4e-a470-c5fc16e13780/tecnico_mascot_1774560256369.png").convert("RGBA")
pixels = img.load()

width, height = img.size
target_color = (255, 255, 255) # Pure white
threshold = 30 # distance threshold for full transparency
soft_edge = 30 # distance range for partial transparency

for y in range(height):
    for x in range(width):
        r, g, b, a = pixels[x, y]
        
        # Calculate distance to white
        dist = math.sqrt((r - 255)**2 + (g - 255)**2 + (b - 255)**2)
        
        if dist <= threshold:
            # It's pure white or very close -> fully transparent
            pixels[x, y] = (r, g, b, 0)
        elif dist <= threshold + soft_edge:
            # It's a border pixel -> partial transparency (anti-aliasing)
            # scale alpha linearly from 0 to 255 based on distance
            alpha_ratio = (dist - threshold) / soft_edge
            new_alpha = int(255 * alpha_ratio)
            # We also might want to subtract some white from the pixel so it doesn't look like a white halo
            # A simple fix is just to use the alpha for smoothing
            pixels[x, y] = (r, g, b, new_alpha)

# Also crop out transparent whitespace
bbox = img.getbbox()
if bbox:
    img = img.crop(bbox)

img.save("/home/vircom/.gemini/antigravity/scratch/climasdeldesierto/public/images/tecnico_bgless.png")
print("Saved better cutout")
