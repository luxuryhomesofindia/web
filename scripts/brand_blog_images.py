#!/usr/bin/env python3
"""
Luxury Homes of India (LHI) - Image Branding Script

Applies standardized LHI luxury branding to blog images:
1. Centered official logo watermark at 40% opacity, fixed 165x88 px (1:1 native scale).
2. Top contact banner: 46px translucent ivory (#FAF8F5, 95% opacity) with a 2px
   gold (#C99C3B) bottom accent border and bold "+91 90922 76222" charcoal text.
3. Bottom website banner: 46px translucent ivory with a 2px gold top accent border
   and bold "www.luxuryhomesofindia.in" charcoal text.
4. Automatic non-destructive backup of original assets to `assets/images/blog/_originals/`.

Usage:
  # Process all images in assets/images/blog
  python scripts/brand_blog_images.py --all

  # Process specific images
  python scripts/brand_blog_images.py image1.webp image2.webp
"""

import os
import sys
import shutil
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from PIL import Image, ImageDraw, ImageFont

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
BLOG_DIR = os.path.join(ROOT_DIR, 'assets', 'images', 'blog')
ORIGINALS_DIR = os.path.join(BLOG_DIR, '_originals')
LOGO_PATH = os.path.join(ROOT_DIR, 'assets', 'images', 'footer', 'logo.png')

LOGO_W, LOGO_H = 165, 88
LOGO_OPACITY = 0.40
BANNER_HEIGHT = 46

IVORY_BG = (250, 248, 245, 242)   # 95% opacity pearl-white / ivory
GOLD_ACCENT = (201, 156, 59, 255) # LHI Gold #C99C3B
TEXT_COLOR = (26, 26, 26, 255)    # High-contrast charcoal #1A1A1A

PHONE_TEXT = '+91 90922 76222'
URL_TEXT = 'www.luxuryhomesofindia.in'

FONT_PATH = 'C:/Windows/Fonts/segoeuib.ttf'

os.makedirs(ORIGINALS_DIR, exist_ok=True)

# Prepare watermark
if not os.path.exists(LOGO_PATH):
    raise FileNotFoundError(f"Logo not found at {LOGO_PATH}")

_logo_raw = Image.open(LOGO_PATH).convert('RGBA')
if _logo_raw.size != (LOGO_W, LOGO_H):
    _logo_raw = _logo_raw.resize((LOGO_W, LOGO_H), Image.Resampling.LANCZOS)

_r, _g, _b, _a = _logo_raw.split()
_a = _a.point(lambda p: int(round(p * LOGO_OPACITY)))
WATERMARK_IMG = Image.merge('RGBA', (_r, _g, _b, _a))

FONT_TOP = ImageFont.truetype(FONT_PATH, 21)
FONT_BOTTOM = ImageFont.truetype(FONT_PATH, 19)

def process_image(filename):
    src_path = os.path.join(BLOG_DIR, filename)
    backup_path = os.path.join(ORIGINALS_DIR, filename)
    ext = os.path.splitext(filename)[1].lower()

    if not os.path.exists(src_path) and not os.path.exists(backup_path):
        return False, filename, "File not found"

    # 1. Back up original if not already backed up
    if not os.path.exists(backup_path):
        shutil.copy2(src_path, backup_path)

    # 2. Open pristine original from backup
    with Image.open(backup_path) as im:
        orig = im.convert('RGBA')
    w, h = orig.size

    # 3. Watermark placement
    logo_x = (w - LOGO_W) // 2
    logo_y = (h - LOGO_H) // 2

    base = Image.new('RGBA', (w, h))
    base.paste(orig, (0, 0))
    base.paste(WATERMARK_IMG, (logo_x, logo_y), WATERMARK_IMG)

    # 4. Banner overlays
    overlay = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)

    # Top banner & 2px gold border
    draw.rectangle([0, 0, w, BANNER_HEIGHT], fill=IVORY_BG)
    draw.line([(0, BANNER_HEIGHT - 1), (w, BANNER_HEIGHT - 1)], fill=GOLD_ACCENT, width=2)

    # Bottom banner & 2px gold border
    draw.rectangle([0, h - BANNER_HEIGHT, w, h], fill=IVORY_BG)
    draw.line([(0, h - BANNER_HEIGHT), (w, h - BANNER_HEIGHT)], fill=GOLD_ACCENT, width=2)

    final_rgba = Image.alpha_composite(base, overlay)
    final_draw = ImageDraw.Draw(final_rgba)

    # Centered typography
    final_draw.text((w // 2, 22), PHONE_TEXT, fill=TEXT_COLOR, font=FONT_TOP, anchor='mm')
    final_draw.text((w // 2, h - (BANNER_HEIGHT // 2) + 1), URL_TEXT, fill=TEXT_COLOR, font=FONT_BOTTOM, anchor='mm')

    # 5. Save back to original format
    if ext == '.webp':
        final_rgb = final_rgba.convert('RGB')
        final_rgb.save(src_path, 'WEBP', quality=92, method=6)
    elif ext in ['.jpg', '.jpeg']:
        final_rgb = final_rgba.convert('RGB')
        final_rgb.save(src_path, 'JPEG', quality=92)
    elif ext == '.png':
        final_rgba.save(src_path, 'PNG', optimize=True)
    else:
        final_rgb = final_rgba.convert('RGB')
        final_rgb.save(src_path, quality=92)

    return True, filename, f"{w}x{h}"

def main():
    args = sys.argv[1:]
    if not args or '--all' in args:
        targets = [
            f for f in os.listdir(BLOG_DIR)
            if os.path.isfile(os.path.join(BLOG_DIR, f))
            and os.path.splitext(f)[1].lower() in ['.webp', '.jpg', '.jpeg', '.png']
            and not f.startswith('.')
        ]
    else:
        targets = [os.path.basename(a) for a in args]

    print(f"Target count: {len(targets)} images")
    start_time = time.time()
    completed = 0
    errors = 0

    with ThreadPoolExecutor(max_workers=8) as executor:
        futures = {executor.submit(process_image, f): f for f in targets}
        for future in as_completed(futures):
            f_name = futures[future]
            try:
                ok, fname, details = future.result()
                if ok:
                    completed += 1
                else:
                    errors += 1
                    print(f"Failed {fname}: {details}")
                if completed % 50 == 0 or completed == len(targets):
                    print(f"Processed: {completed}/{len(targets)}...")
            except Exception as e:
                errors += 1
                print(f"Error {f_name}: {e}")

    elapsed = time.time() - start_time
    print(f"\nFinished in {elapsed:.2f}s | Success: {completed} | Errors: {errors}")

if __name__ == '__main__':
    main()
