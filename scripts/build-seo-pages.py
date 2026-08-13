import os
import json
import re
import shutil

# Directories
WORKSPACE_ROOT = "d:/Web_Projects/LHI"
SEO_DATA_DIR = os.path.join(WORKSPACE_ROOT, "seo-data")
PRICING_DIR = os.path.join(SEO_DATA_DIR, "pricing")

# Load Configuration Data
with open(os.path.join(SEO_DATA_DIR, "pages.json"), "r", encoding="utf-8") as f:
    pages_data = json.load(f)

with open(os.path.join(SEO_DATA_DIR, "localities.json"), "r", encoding="utf-8") as f:
    localities_data = json.load(f)

with open(os.path.join(PRICING_DIR, "construction-packages.json"), "r", encoding="utf-8") as f:
    pricing_data = json.load(f)

# Load Package Specs
package_specs = {}
for p_type in ["basic", "standard", "premium"]:
    with open(os.path.join(PRICING_DIR, f"{p_type}.json"), "r", encoding="utf-8") as f:
        package_specs[p_type] = json.load(f)

# Read Template
with open(os.path.join(WORKSPACE_ROOT, "template.html"), "r", encoding="utf-8") as f:
    template_content = f.read()

def get_base_template(title, description, canonical_url, robots_meta="index, follow", schema_json="", faq_schema=""):
    """
    Extracts head, header, footer, modal and script blocks from template.html and replaces placeholders.
    """
    head_match = re.search(r"<head>(.*?)</head>", template_content, re.DOTALL)
    header_match = re.search(r"<header id=\"mainHeader\">(.*?)</header>", template_content, re.DOTALL)
    footer_match = re.search(r"<footer>(.*?)</footer>", template_content, re.DOTALL)
    modals_match = re.search(r"<!-- MODALS -->(.*?)<!-- WHATSAPP FLOAT -->", template_content, re.DOTALL)
    script_match = re.search(r"<script>(.*?)</script>", template_content, re.DOTALL)

    head_str = head_match.group(1) if head_match else ""
    header_str = header_match.group(1) if header_match else ""
    footer_str = footer_match.group(1) if footer_match else ""
    modals_str = modals_match.group(1) if modals_match else ""
    script_str = script_match.group(1) if script_match else ""

    # Replace meta parameters in head
    head_str = re.sub(r"<title>.*?</title>", f"<title>{title}</title>", head_str)
    head_str = re.sub(r"<meta name=\"description\" content=\".*?\"[^>]*>", f'<meta name="description" content="{description}">', head_str)
    head_str = re.sub(r"<link rel=\"canonical\" href=\".*?\"[^>]*>", f'<link rel="canonical" href="{canonical_url}">', head_str)
    head_str = re.sub(r"<meta name=\"robots\" content=\".*?\"[^>]*>", f'<meta name="robots" content="{robots_meta}">', head_str)

    # Dynamic OpenGraph and Twitter tags
    head_str = re.sub(r"<meta property=\"og:title\" content=\".*?\"[^>]*>", f'<meta property="og:title" content="{title}">', head_str)
    head_str = re.sub(r"<meta property=\"og:description\" content=\".*?\"[^>]*>", f'<meta property="og:description" content="{description}">', head_str)
    head_str += f'\n    <meta property="og:url" content="{canonical_url}">'
    head_str += f'\n    <meta name="twitter:card" content="summary_large_image">'
    head_str += f'\n    <meta name="twitter:title" content="{title}">'
    head_str += f'\n    <meta name="twitter:description" content="{description}">'
    head_str += f'\n    <meta name="twitter:image" content="https://luxuryhomesofindia.in/assets/images/logo/logo.png">'

    # Insert Schema if present
    schema_block = ""
    if schema_json:
        schema_block += f'\n    <script type="application/ld+json">\n{schema_json}\n    </script>'
    if faq_schema:
        schema_block += f'\n    <script type="application/ld+json">\n{faq_schema}\n    </script>'
    
    # Clean up standard layout placeholders inside head_str
    head_str = head_str.replace("{{JSON_LD}}", "").replace("{{FAQ_SCHEMA}}", "")
    head_str += schema_block

    # Return base structure
    return {
        "head": head_str,
        "header": header_str,
        "footer": footer_str,
        "modals": modals_str,
        "scripts": script_str
    }

def make_tldr_and_byline(page_key, page):
    """Generates a high-impact TL;DR under 60 words and visible author attribution."""
    if page.get("page_type") == "locality":
        slug = page_key.split("/")[-1]
        loc = localities_data.get(slug, {})
        tldr_text = f"LHI is the premier turnkey design-build builder in {loc.get('name', 'Chennai')}, specializing in luxury villa construction, custom architecture, and foundations optimized for the local soil conditions at verified rates from ₹2,250 to ₹2,650/sq.ft."
    else:
        tldr_text = f"LHI is Chennai's leading turnkey residential contractor, providing Vastu-compliant architecture, structural engineering, and custom interior design at approved pricing packages (₹2,250 to ₹2,650/sq.ft) under strict milestone contracts."
    
    return f"""
    <div class="tldr-box" style="margin: 30px auto; max-width: 850px; background: rgba(198,162,90,0.05); border-left: 4px solid var(--gold-primary); padding: 25px; font-size: 0.95rem; line-height: 1.6; color: #ccc;">
        <strong>TL;DR Summary:</strong> {tldr_text}
        <div style="margin-top: 12px; font-size: 0.8rem; color: #888; font-style: italic; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-user-edit" style="color: var(--gold-primary);"></i>
            <span>Written by: <strong>Luxury Homes of India Editorial Team</strong></span>
        </div>
    </div>
    """

def generate_layout(page_key, body_html, custom_js=""):
    """
    Assembles the fully styled static HTML page matching LHI standards.
    """
    page = pages_data[page_key]
    title = page["title"]
    description = page["description"]
    canonical_url = f"https://luxuryhomesofindia.in/{page_key}/" if page_key != "chennai" else "https://luxuryhomesofindia.in/chennai/"
    
    # Generate JSON-LD Organization & Breadcrumb
    breadcrumbs = [
        {"name": "Home", "item": "https://luxuryhomesofindia.in/"}
    ]
    if "/" in page_key:
        parent_dir = page_key.split("/")[0]
        breadcrumbs.append({"name": parent_dir.capitalize(), "item": f"https://luxuryhomesofindia.in/{parent_dir}/"})
    breadcrumbs.append({"name": page["h1"], "item": canonical_url})
    
    breadcrumb_schema = {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": idx + 1,
                "name": b["name"],
                "item": b["item"]
            } for idx, b in enumerate(breadcrumbs)
        ]
    }

    article_schema = {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": title,
        "description": description,
        "image": "https://luxuryhomesofindia.in/assets/images/logo/logo.png",
        "author": {
            "@type": "Organization",
            "name": "Luxury Homes of India Editorial Team"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Luxury Homes of India",
            "logo": {
                "@type": "ImageObject",
                "url": "https://luxuryhomesofindia.in/assets/images/logo/logo.png"
            }
        },
        "datePublished": "2026-08-13",
        "dateModified": "2026-08-13",
        "mainEntityOfPage": canonical_url
    }

    combined_schema = [breadcrumb_schema, article_schema]

    faq_schema = ""
    if page.get("faqs"):
        faq_schema = json.dumps({
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [
                {
                    "@type": "Question",
                    "name": q["question"],
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": q["answer"]
                    }
                } for q in page["faqs"]
            ]
        }, indent=2)

    base = get_base_template(
        title=title,
        description=description,
        canonical_url=canonical_url,
        robots_meta="index, follow",
        schema_json=json.dumps(combined_schema, indent=2),
        faq_schema=faq_schema
    )

    tldr_block = make_tldr_and_byline(page_key, page)
    if "</section>" in body_html:
        body_html = body_html.replace("</section>", "</section>\n" + tldr_block, 1)
    else:
        body_html = tldr_block + body_html

    full_html = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {base["head"]}
</head>
<body>
    <header id="mainHeader">
        {base["header"]}
    </header>
    
    {body_html}

    <footer>
        {base["footer"]}
    </footer>

    <!-- MODALS -->
    {base["modals"]}

    <!-- WHATSAPP FLOAT -->
    <a href="https://wa.me/919092276222" target="_blank" class="wa-float"><i class="fab fa-whatsapp"></i></a>

    <script>
        {base["scripts"]}
        {custom_js}
    </script>
</body>
</html>"""
    return full_html

def make_pricing_table():
    """Generates the visible HTML package comparison pricing table."""
    return """
    <div class="pricing-table-container" style="margin: 40px 0; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; background: #0a0a0a; border: 1px solid rgba(198,162,90,0.3);">
            <thead>
                <tr style="background: rgba(198,162,90,0.1); border-bottom: 2px solid var(--gold-primary);">
                    <th style="padding: 15px; color: #fff; font-weight: 700;">Package</th>
                    <th style="padding: 15px; color: #fff; font-weight: 700;">Rate (sq.ft)</th>
                    <th style="padding: 15px; color: #fff; font-weight: 700;">Key Specifications</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 15px; font-weight: bold; color: var(--gold-primary);">Basic</td>
                    <td style="padding: 15px; font-weight: bold; color: #fff;">₹2,250</td>
                    <td style="padding: 15px; color: #ccc; font-size: 0.85rem;">Priya/Maha Cement, Arun/Kamachi Steel, Flyash/AAC Blocks, Country Wood Doors</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 15px; font-weight: bold; color: var(--gold-primary);">Standard</td>
                    <td style="padding: 15px; font-weight: bold; color: #fff;">₹2,450</td>
                    <td style="padding: 15px; color: #ccc; font-size: 0.85rem;">Zurari/Chettinad Cement, ARS/Suryadev Steel, Chamber Clay Bricks, African Teak Main Door</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.3);">
                    <td style="padding: 15px; font-weight: bold; color: var(--gold-primary);">Premium</td>
                    <td style="padding: 15px; font-weight: bold; color: #fff;">₹2,650</td>
                    <td style="padding: 15px; color: #ccc; font-size: 0.85rem;">Coromandel/UltraTech Cement, Tata/JSW Steel, Machine Clay Bricks, Full African Teak Frames</td>
                </tr>
            </tbody>
        </table>
        <p style="font-size: 0.8rem; color: #777; margin-top: 10px; font-style: italic;">
            * Disclaimer: Rates are based on official LHI Quotation estimates. Actual project pricing is subject to structural plans, site topography, and official signed LHI contracts.
        </p>
    </div>
    """

def make_faq_accordions(faqs):
    """Generates standard HTML FAQ accordion structures."""
    if not faqs:
        return ""
    accordions = ""
    for idx, f in enumerate(faqs):
        accordions += f"""
        <div class="faq-item">
            <div class="faq-header">
                <h4>{f["question"]}</h4>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-content">
                <p>{f["answer"]}</p>
            </div>
        </div>
        """
    return f"""
    <section id="faq">
        <div class="faq-container">
            <h2 class="gold-text" style="text-align: center; margin-bottom: 30px;">Frequently Asked Questions</h2>
            {accordions}
        </div>
    </section>
    """

# 1. GENERATE CHENNAI HUB PAGE
def build_chennai_hub():
    page_key = "chennai"
    body_html = f"""
    <section class="hero" style="height: 60vh;">
        <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
        <div class="hero-content">
            <h1 class="gold-text">{pages_data[page_key]["h1"]}</h1>
            <p>{pages_data[page_key]["intro"]}</p>
        </div>
    </section>

    <section id="why-lhi">
        <div class="section-header">
            <h2 class="gold-text">Why Choose Luxury Homes of India</h2>
            <p>We are dedicated to building structural legacies with verified transparent pricing models.</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <i class="fas fa-handshake"></i>
                <h3>Bespoke Engineering</h3>
                <p>Tata/JSW steel framing, Coromandel/UltraTech cement, and complete foundation safety.</p>
            </div>
            <div class="service-card">
                <i class="fas fa-compass-drafting"></i>
                <h3>Bespoke Layouts</h3>
                <p>Custom spaces optimized under prevailing CMDA planning guidelines.</p>
            </div>
        </div>
    </section>

    <section id="pricing-tiers" style="background: var(--black-matte);">
        <div class="section-header">
            <h2 class="gold-text">Official LHI Pricing Packages</h2>
            <p>Our approved turnkey residential construction rates in Chennai.</p>
        </div>
        <div class="faq-container">
            {make_pricing_table()}
        </div>
    </section>
    
    {make_faq_accordions(pages_data[page_key]["faqs"])}

    <section id="contact" style="text-align: center; background: #0a0a0a;">
        <h2 class="gold-text">Calculate Your Home Estimation Cost</h2>
        <p style="margin-bottom: 30px; color: #888;">Input your built-up space criteria to retrieve a complete cost breakdown instantly.</p>
        <a href="/chennai/construction-cost-calculator/" class="btn btn-gold">Cost Estimator Calculator</a>
    </section>
    """
    html_content = generate_layout(page_key, body_html)
    dest_path = os.path.join(WORKSPACE_ROOT, "chennai", "index.html")
    os.makedirs(os.path.dirname(dest_path), exist_ok=True)
    with open(dest_path, "w", encoding="utf-8") as f:
        f.write(html_content)

# 2. GENERATE SERVICE CLUSTER PAGES
def build_services():
    for page_key, page in pages_data.items():
        if page_type := page.get("page_type"):
            if page_type == "service" and page_key != "chennai/construction-cost-calculator/":
                # Build Service Page Content
                body_html = f"""
                <section class="hero" style="height: 60vh;">
                    <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
                    <div class="hero-content">
                        <h1 class="gold-text">{page["h1"]}</h1>
                        <p>{page["intro"]}</p>
                    </div>
                </section>

                <section id="details">
                    <div class="faq-container">
                        <h2 class="gold-text" style="margin-bottom: 20px;">Premium Residential Services</h2>
                        <p style="color: #ccc; margin-bottom: 20px;">LHI provides turnkey engineering execution in Chennai, utilizing Tata/JSW steel, machine-cut bricks, and African Teakwood frame fittings according to official quotation plans.</p>
                        
                        {"<h3>Approved Cost Estimation Guide</h3>" + make_pricing_table() if "cost" in page_key else ""}
                    </div>
                </section>
                
                {make_faq_accordions(page["faqs"])}

                <section id="cta" style="text-align: center; background: var(--black-matte);">
                    <h2 class="gold-text">Consult With Our Construction Experts</h2>
                    <p style="margin-bottom: 30px; color: #888;">Begin your legacy home layout planning today. Talk to a turnkey advisor.</p>
                    <a href="javascript:void(0)" onclick="openModal('enquiryModal')" class="btn btn-gold">Request Consultation</a>
                </section>
                """
                html_content = generate_layout(page_key, body_html)
                dest_path = os.path.join(WORKSPACE_ROOT, page_key, "index.html")
                os.makedirs(os.path.dirname(dest_path), exist_ok=True)
                with open(dest_path, "w", encoding="utf-8") as f:
                    f.write(html_content)

# 3. GENERATE DYNAMIC COST CALCULATOR
def build_cost_calculator():
    page_key = "chennai/construction-cost-calculator"
    body_html = f"""
    <section class="hero" style="height: 50vh;">
        <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
        <div class="hero-content">
            <h1 class="gold-text">{pages_data[page_key]["h1"]}</h1>
            <p>{pages_data[page_key]["intro"]}</p>
        </div>
    </section>

    <section id="calculator-section">
        <div class="faq-container" style="background: #0a0a0a; padding: 40px; border: 1px solid rgba(198,162,90,0.3);">
            <h3 class="gold-text" style="margin-bottom: 30px; text-align: center;">Enter Your Build Area Specification</h3>
            
            <div class="form-group">
                <label>Built-up Area (Sq.Ft)</label>
                <input type="number" id="calcArea" class="form-control" placeholder="e.g. 2400" min="100" max="100000" required>
            </div>

            <div class="form-group">
                <label>Optional Additions (Not included in standard packages)</label>
                <div style="margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <label style="color: #ccc; text-transform: none; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="addPool"> Swimming Pool
                    </label>
                    <label style="color: #ccc; text-transform: none; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="addElevator"> Private Elevator
                    </label>
                </div>
            </div>

            <button onclick="calculateEstimates()" class="btn btn-gold" style="width: 100%; margin-top: 20px;">Calculate Estimate</button>

            <!-- Results Output -->
            <div id="calcResults" style="margin-top: 40px; display: none; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px;">
                <h4 class="gold-text" style="margin-bottom: 20px; text-align: center;">Indicative Cost Breakdown</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center; margin-bottom: 30px;">
                    <div style="background: var(--charcoal); padding: 20px; border: 1px solid rgba(255,255,255,0.05);">
                        <h5 style="color: var(--gold-primary); margin-bottom: 10px;">Basic Package</h5>
                        <div id="costBasic" style="font-size: 1.3rem; font-weight: bold;">-</div>
                        <p style="font-size: 0.75rem; color: #666; margin-top: 5px;">₹2,250/sq.ft</p>
                    </div>
                    <div style="background: var(--charcoal); padding: 20px; border: 1px solid var(--gold-primary);">
                        <h5 style="color: var(--gold-primary); margin-bottom: 10px;">Standard Package</h5>
                        <div id="costStandard" style="font-size: 1.3rem; font-weight: bold;">-</div>
                        <p style="font-size: 0.75rem; color: #666; margin-top: 5px;">₹2,450/sq.ft</p>
                    </div>
                    <div style="background: var(--charcoal); padding: 20px; border: 1px solid rgba(255,255,255,0.05);">
                        <h5 style="color: var(--gold-primary); margin-bottom: 10px;">Premium Package</h5>
                        <div id="costPremium" style="font-size: 1.3rem; font-weight: bold;">-</div>
                        <p style="font-size: 0.75rem; color: #666; margin-top: 5px;">₹2,650/sq.ft</p>
                    </div>
                </div>

                <div id="unapprovedWarning" style="display: none; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 15px; text-align: center; margin-bottom: 20px; font-size: 0.85rem; color: #ef4444;">
                    Some selected options require a project-specific quotation and are not included in the current approved pricing data.
                </div>

                <div style="font-size: 0.8rem; color: #777; font-style: italic; line-height: 1.4;">
                    * Cost estimates are calculated dynamically from LHI base rates (Basic: ₹2,250/sq.ft, Standard: ₹2,450/sq.ft, Premium: ₹2,650/sq.ft). 
                    Calculations are indicative estimations and do not constitute a final binding contract. Material specifications are governed strictly by LHI package guidelines.
                </div>
            </div>
        </div>
    </section>

    {make_faq_accordions(pages_data[page_key]["faqs"])}
    """

    custom_js = """
    function calculateEstimates() {
        const areaInput = document.getElementById('calcArea');
        const area = parseFloat(areaInput.value);
        if (!area || area <= 0) {
            alert('Please enter a valid built-up area.');
            return;
        }

        const addPool = document.getElementById('addPool').checked;
        const addElevator = document.getElementById('addElevator').checked;

        // Formula Calculation
        const basicTotal = area * 2250;
        const standardTotal = area * 2450;
        const premiumTotal = area * 2650;

        document.getElementById('costBasic').innerText = '₹' + basicTotal.toLocaleString('en-IN');
        document.getElementById('costStandard').innerText = '₹' + standardTotal.toLocaleString('en-IN');
        document.getElementById('costPremium').innerText = '₹' + premiumTotal.toLocaleString('en-IN');

        const warningDiv = document.getElementById('unapprovedWarning');
        if (addPool || addElevator) {
            warningDiv.style.display = 'block';
        } else {
            warningDiv.style.display = 'none';
        }

        document.getElementById('calcResults').style.display = 'block';
        document.getElementById('calcResults').scrollIntoView({ behavior: 'smooth' });
    }
    """

    html_content = generate_layout(page_key, body_html, custom_js)
    dest_path = os.path.join(WORKSPACE_ROOT, "chennai", "construction-cost-calculator", "index.html")
    os.makedirs(os.path.dirname(dest_path), exist_ok=True)
    with open(dest_path, "w", encoding="utf-8") as f:
        f.write(html_content)

# 4. GENERATE LOCALITY PAGES
def build_localities():
    for slug, loc in localities_data.items():
        page_key = f"chennai/{slug}"
        if page_key in pages_data:
            body_html = f"""
            <section class="hero" style="height: 60vh;">
                <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
                <div class="hero-content">
                    <h1 class="gold-text">{pages_data[page_key]["h1"]}</h1>
                    <p>{loc["overview"]}</p>
                </div>
            </section>

            <section id="locality-details">
                <div class="faq-container">
                    <h2 class="gold-text" style="margin-bottom: 20px;">Residential Development in {loc["name"]}</h2>
                    
                    <div style="background: var(--charcoal); padding: 30px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px;">
                        <h4 style="color: var(--gold-primary); margin-bottom: 10px;"><i class="fas fa-building-circle-check"></i> Construction & Geological Considerations</h4>
                        <p style="color: #ccc; font-size: 0.95rem;">{loc["construction_context"]}</p>
                    </div>

                    <div style="background: var(--charcoal); padding: 30px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 40px;">
                        <h4 style="color: var(--gold-primary); margin-bottom: 10px;"><i class="fas fa-file-signature"></i> Regulatory Clearances</h4>
                        <p style="color: #ccc; font-size: 0.95rem;">{loc["approvals"]}</p>
                    </div>

                    <h3 class="gold-text" style="margin-bottom: 20px;">Indicative Construction Costs for {loc["name"]}</h3>
                    {make_pricing_table()}
                </div>
            </section>

            {make_faq_accordions(loc["faqs"])}

            <section id="cta" style="text-align: center; background: var(--black-matte);">
                <h2 class="gold-text">Begin Your Project in {loc["name"]}</h2>
                <p style="margin-bottom: 30px; color: #888;">Calculate costs or contact our engineers to plan your villa foundation design.</p>
                <a href="javascript:void(0)" onclick="openModal('enquiryModal')" class="btn btn-gold">Request Site Consultation</a>
            </section>
            """
            html_content = generate_layout(page_key, body_html)
            dest_path = os.path.join(WORKSPACE_ROOT, page_key, "index.html")
            os.makedirs(os.path.dirname(dest_path), exist_ok=True)
            with open(dest_path, "w", encoding="utf-8") as f:
                f.write(html_content)

# 5. GENERATE TAMIL NADU REGIONAL HUB PAGE
def build_tamil_nadu_hub():
    page_key = "tamil-nadu"
    body_html = f"""
    <section class="hero" style="height: 60vh;">
        <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
        <div class="hero-content">
            <h1 class="gold-text">{pages_data[page_key]["h1"]}</h1>
            <p>{pages_data[page_key]["intro"]}</p>
        </div>
    </section>

    <section id="cities-overview">
        <div class="faq-container">
            <h2 class="gold-text" style="margin-bottom: 20px; text-align: center;">Our Tamil Nadu Service Coverage</h2>
            <p style="color: #ccc; text-align: center; margin-bottom: 40px;">
                While Chennai remains our primary commercial location, LHI provides architecture, structural engineering, and turnkey construction supervision across major Tamil Nadu hubs.
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="background: var(--charcoal); padding: 30px; border: 1px solid rgba(255,255,255,0.05);">
                    <h4 style="color: var(--gold-primary); margin-bottom: 10px;">Primary Market</h4>
                    <p style="color: #fff; font-weight: bold; font-size: 1.1rem; margin-bottom: 10px;">Chennai & Suburbs</p>
                    <p style="color: #888; font-size: 0.9rem;">Full design-build turnkey execution including soil testing, CMDA approvals, and onsite civil labor supervision.</p>
                </div>
                <div style="background: var(--charcoal); padding: 30px; border: 1px solid rgba(255,255,255,0.05);">
                    <h4 style="color: var(--gold-primary); margin-bottom: 10px;">Secondary Tamil Nadu Markets</h4>
                    <p style="color: #fff; font-weight: bold; font-size: 1.1rem; margin-bottom: 10px;">Coimbatore, Madurai, Salem, Trichy, Hosur</p>
                    <p style="color: #888; font-size: 0.9rem;">Turnkey structural consultancies, Vastu-compliant architectural planning, and custom design approvals.</p>
                </div>
            </div>
        </div>
    </section>
    
    {make_faq_accordions(pages_data[page_key]["faqs"])}

    <section id="cta" style="text-align: center; background: var(--black-matte);">
        <h2 class="gold-text">Request Services Across Tamil Nadu</h2>
        <p style="margin-bottom: 30px; color: #888;">Connect with our structural engineers to verify service availability in your locality.</p>
        <a href="javascript:void(0)" onclick="openModal('enquiryModal')" class="btn btn-gold">Contact Turnkey Team</a>
    </section>
    """
    html_content = generate_layout(page_key, body_html)
    dest_path = os.path.join(WORKSPACE_ROOT, "tamil-nadu", "index.html")
    os.makedirs(os.path.dirname(dest_path), exist_ok=True)
    with open(dest_path, "w", encoding="utf-8") as f:
        f.write(html_content)

# 6. GENERATE GUIDES & AUTHORITY PAGES
def build_guides():
    for page_key, page in pages_data.items():
        if page_type := page.get("page_type"):
            if page_type == "guide" or page_key in ["questions", "projects"]:
                # Build Guide content
                body_html = f"""
                <section class="hero" style="height: 50vh;">
                    <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
                    <div class="hero-content">
                        <h1 class="gold-text">{page["h1"]}</h1>
                        <p>{page["intro"]}</p>
                    </div>
                </section>

                <section id="content-body">
                    <div class="faq-container">
                        <h2 class="gold-text" style="margin-bottom: 20px;">LHI Authoritative Guidance</h2>
                        <p style="color: #ccc; line-height: 1.8; margin-bottom: 30px;">
                            Building a luxury house requires structural planning, compliance, and material specification auditing. This guide is compiled by LHI's structural engineers to provide complete transparency based on verified construction rates.
                        </p>

                        {"<h3>Approved Turnkey Pricing Guide</h3>" + make_pricing_table() if "cost" in page_key else ""}
                    </div>
                </section>
                
                {make_faq_accordions(page["faqs"])}

                <section id="cta" style="text-align: center; background: var(--black-matte);">
                    <h2 class="gold-text">Begin Your Luxury Home Construction</h2>
                    <p style="margin-bottom: 30px; color: #888;">Calculate estimated costs or consult with an LHI residential expert.</p>
                    <a href="/chennai/construction-cost-calculator/" class="btn btn-gold">Use Cost Calculator</a>
                </section>
                """
                html_content = generate_layout(page_key, body_html)
                dest_path = os.path.join(WORKSPACE_ROOT, page_key, "index.html")
                os.makedirs(os.path.dirname(dest_path), exist_ok=True)
                with open(dest_path, "w", encoding="utf-8") as f:
                    f.write(html_content)

# 7. GENERATE PROJECT PAGES
def build_projects():
    for page_key, page in pages_data.items():
        if page_type := page.get("page_type"):
            if page_type == "project":
                body_html = f"""
                <section class="hero" style="height: 50vh;">
                    <div class="hero-overlay" style="background: rgba(0,0,0,0.85);"></div>
                    <div class="hero-content">
                        <h1 class="gold-text">{page["h1"]}</h1>
                        <p>{page["intro"]}</p>
                    </div>
                </section>

                <section id="case-study">
                    <div class="faq-container">
                        <h2 class="gold-text" style="margin-bottom: 20px;">Project Overview & Specifications</h2>
                        <p style="color: #ccc; margin-bottom: 30px;">
                            This residence represents LHI's dedication to architectural craftsmanship, featuring robust structural foundations, custom African Teak woodwork, premium weathering layers, and structural FSI optimizations.
                        </p>

                        <div style="background: var(--charcoal); padding: 30px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px;">
                            <h4 style="color: var(--gold-primary); margin-bottom: 10px;">Verification Metrics</h4>
                            <ul style="list-style: none; color: #ccc;">
                                <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--gold-primary);"></i> Structural Quality: Premium LHI Specification Grade</li>
                                <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--gold-primary);"></i> Design Conformity: CMDA / Vastu Compliant</li>
                                <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--gold-primary);"></i> Status: Fully Completed & Handed Over</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section id="cta" style="text-align: center; background: var(--black-matte);">
                    <h2 class="gold-text">Build Your Custom Home Layout</h2>
                    <p style="margin-bottom: 30px; color: #888;">Connect with our turnkey builders to start planning your home today.</p>
                    <a href="javascript:void(0)" onclick="openModal('enquiryModal')" class="btn btn-gold">Plan Your Home</a>
                </section>
                """
                html_content = generate_layout(page_key, body_html)
                dest_path = os.path.join(WORKSPACE_ROOT, page_key, "index.html")
                os.makedirs(os.path.dirname(dest_path), exist_ok=True)
                with open(dest_path, "w", encoding="utf-8") as f:
                    f.write(html_content)

def build_sitemap():
    sitemap_path = os.path.join(WORKSPACE_ROOT, "sitemap.xml")
    xml_urls = ""
    for page_key in pages_data.keys():
        canonical_url = f"https://luxuryhomesofindia.in/{page_key}/" if page_key != "chennai" else "https://luxuryhomesofindia.in/chennai/"
        xml_urls += f"""  <url>
    <loc>{canonical_url}</loc>
    <lastmod>2026-08-13</lastmod>
    <changefreq>daily</changefreq>
    <priority>0.85</priority>
  </url>\n"""

    sitemap_xml = f"""<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{xml_urls}</urlset>"""

    with open(sitemap_path, "w", encoding="utf-8") as f:
        f.write(sitemap_xml)
    print("Sitemap sitemap.xml generated successfully.")

if __name__ == "__main__":
    print("Beginning Phase 1 LHI static page build...")
    build_chennai_hub()
    build_services()
    build_cost_calculator()
    build_localities()
    build_tamil_nadu_hub()
    build_guides()
    build_projects()
    build_sitemap()
    print("Phase 1 Static HTML Pages and sitemap generated successfully!")
