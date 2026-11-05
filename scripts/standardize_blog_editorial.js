const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');
const verifiedDateMap = JSON.parse(fs.readFileSync(path.join(ROOT_DIR, 'scratch', 'verified_date_map.json'), 'utf8'));

const TARGET_WRITTEN_BY = 'LHI Editorial Team';
const TARGET_REVIEWED_BY = 'LHI In-House Technical Team';

const report = {
    totalAudited: 0,
    articlesUpdated: 0,
    articles2025: 0,
    articles2026: 0,
    articlesNeedingReview: [],
    modifiedFiles: [],
    perFileDetails: []
};

// 1. Process 153 Blog Article Files
Object.keys(verifiedDateMap).sort().forEach(file => {
    report.totalAudited++;
    const filePath = path.join(ROOT_DIR, file);
    if (!fs.existsSync(filePath)) {
        console.warn(`File not found: ${file}`);
        return;
    }

    const dateInfo = verifiedDateMap[file];
    const yr = dateInfo.isoDate ? dateInfo.isoDate.slice(0, 4) : 'UNKNOWN';
    if (yr === '2025') report.articles2025++;
    else if (yr === '2026') report.articles2026++;
    else report.articlesNeedingReview.push({ file, reason: 'Year is not 2025 or 2026' });

    let content = fs.readFileSync(filePath, 'utf8');
    const originalContent = content;
    let fileModified = false;
    const changes = [];

    // --- A. Header Meta Byline ---
    // User byline: <span><i class="fa-... fa-user"></i> ...</span>
    const oldBylineUser = content.match(/<span><i class="fa-(?:regular|solid) fa-user"><\/i>\s*(?:by\s+)?([^<]+)<\/span>/i);
    if (oldBylineUser && oldBylineUser[1].trim() !== TARGET_WRITTEN_BY) {
        content = content.replace(
            /(<span><i class="fa-(?:regular|solid) fa-user"><\/i>\s*)(?:by\s+)?([^<]+)(<\/span>)/gi,
            `$1${TARGET_WRITTEN_BY}$3`
        );
        changes.push(`Header Byline Author: "${oldBylineUser[1].trim()}" -> "${TARGET_WRITTEN_BY}"`);
        fileModified = true;
    }

    // Header byline date: ensure format matches dateInfo.displayDate
    if (dateInfo && dateInfo.displayDate) {
        const oldBylineDate = content.match(/<span><i class="fa-regular fa-calendar-check"><\/i>\s*Published:?\s*([^<]+)<\/span>/i);
        if (oldBylineDate) {
            const currentDisplay = oldBylineDate[1].trim();
            // If it only says e.g. "October 2026", upgrade to exact "October 9, 2026"
            if (currentDisplay !== dateInfo.displayDate && /^[A-Za-z]+\s+\d{4}$/.test(currentDisplay)) {
                content = content.replace(
                    /(<span><i class="fa-regular fa-calendar-check"><\/i>\s*Published:?\s*)([A-Za-z]+\s+\d{4})(<\/span>)/gi,
                    `$1${dateInfo.displayDate}$3`
                );
                changes.push(`Header Byline Date: "${currentDisplay}" -> "${dateInfo.displayDate}"`);
                fileModified = true;
            }
        }
    }

    // Top wrapper if present in legacy templates:
    // <div class="single"><i class="fa-regular fa-circle-user"...></i><span>by ...</span></div>
    if (content.includes('fa-circle-user')) {
        const topUserMatch = content.match(/(<div class="single">\s*<i class="fa-regular fa-circle-user"[^>]*><\/i>\s*<span>by\s+)[^<]+(<\/span>)/i);
        if (topUserMatch) {
            content = content.replace(
                /(<div class="single">\s*<i class="fa-regular fa-circle-user"[^>]*><\/i>\s*<span>by\s+)[^<]+(<\/span>)/gi,
                `$1${TARGET_WRITTEN_BY}$2`
            );
            changes.push(`Top Wrapper Author updated to "${TARGET_WRITTEN_BY}"`);
            fileModified = true;
        }
    }

    // Top meta bar (published date):
    // <span><i class="fa-regular fa-calendar-check"...></i> Published October 23, 2026</span>
    // Ensure display date consistency if month-only was present

    // --- B. JSON-LD Structured Data ---
    content = content.replace(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/gi, (fullMatch, jsonText) => {
        try {
            const data = JSON.parse(jsonText);
            let ldChanged = false;

            function processLdItem(item) {
                if (item['@type'] === 'BlogPosting' || item['@type'] === 'Article' || item['@type'] === 'WebPage') {
                    // Author
                    if (item.author) {
                        if (typeof item.author === 'object') {
                            if (item.author.name !== TARGET_WRITTEN_BY) {
                                changes.push(`JSON-LD Author: "${item.author.name}" -> "${TARGET_WRITTEN_BY}"`);
                                item.author.name = TARGET_WRITTEN_BY;
                                item.author['@type'] = 'Organization';
                                if (!item.author.url) item.author.url = 'https://luxuryhomesofindia.in/';
                                ldChanged = true;
                            }
                        } else if (typeof item.author === 'string') {
                            if (item.author !== TARGET_WRITTEN_BY) {
                                changes.push(`JSON-LD Author: "${item.author}" -> "${TARGET_WRITTEN_BY}"`);
                                item.author = {
                                    '@type': 'Organization',
                                    'name': TARGET_WRITTEN_BY,
                                    'url': 'https://luxuryhomesofindia.in/'
                                };
                                ldChanged = true;
                            }
                        }
                    }

                    // Reviewer
                    if (item.reviewedBy) {
                        if (typeof item.reviewedBy === 'object') {
                            if (item.reviewedBy.name !== TARGET_REVIEWED_BY) {
                                changes.push(`JSON-LD Reviewer: "${item.reviewedBy.name}" -> "${TARGET_REVIEWED_BY}"`);
                                item.reviewedBy.name = TARGET_REVIEWED_BY;
                                item.reviewedBy['@type'] = 'Organization';
                                ldChanged = true;
                            }
                        } else if (typeof item.reviewedBy === 'string') {
                            if (item.reviewedBy !== TARGET_REVIEWED_BY) {
                                changes.push(`JSON-LD Reviewer: "${item.reviewedBy}" -> "${TARGET_REVIEWED_BY}"`);
                                item.reviewedBy = {
                                    '@type': 'Organization',
                                    'name': TARGET_REVIEWED_BY
                                };
                                ldChanged = true;
                            }
                        }
                    }

                    // datePublished alignment
                    if (dateInfo && dateInfo.isoDate) {
                        if (!item.datePublished) {
                            item.datePublished = dateInfo.isoDate;
                            ldChanged = true;
                            changes.push(`JSON-LD datePublished set to "${dateInfo.isoDate}"`);
                        }
                    }
                }
            }

            if (data['@graph'] && Array.isArray(data['@graph'])) {
                data['@graph'].forEach(processLdItem);
            } else {
                processLdItem(data);
            }

            if (ldChanged) {
                fileModified = true;
                return `<script type="application/ld+json">\n${JSON.stringify(data, null, 2)}\n    </script>`;
            }
        } catch (e) {
            // Regex fallback if JSON parse fails
            const regexLd = /("author"\s*:\s*\{\s*"@type"\s*:\s*"[^"]*",\s*"name"\s*:\s*")[^"]+(")/;
            if (regexLd.test(jsonText)) {
                jsonText = jsonText.replace(regexLd, `$1${TARGET_WRITTEN_BY}$2`);
                fileModified = true;
                changes.push(`JSON-LD Author (regex fallback) updated to "${TARGET_WRITTEN_BY}"`);
                return `<script type="application/ld+json">${jsonText}</script>`;
            }
        }
        return fullMatch;
    });

    // --- C. Editorial Information & Disclaimer Box (Bottom) ---
    // 1. Spans: <span><strong>Written by:</strong> ...</span>
    if (/(<span><strong>\s*Written by:?\s*<\/strong>\s*)([^<]+)(<\/span>)/i.test(content)) {
        content = content.replace(
            /(<span><strong>\s*Written by:?\s*<\/strong>\s*)([^<]+)(<\/span>)/gi,
            (m, p1, p2, p3) => {
                if (p2.trim() !== TARGET_WRITTEN_BY) {
                    changes.push(`Editorial Box Written by: "${p2.trim()}" -> "${TARGET_WRITTEN_BY}"`);
                    fileModified = true;
                }
                return `${p1}${TARGET_WRITTEN_BY}${p3}`;
            }
        );
    }

    // 2. Spans: <span><strong>Reviewed by:</strong> ...</span>
    if (/(<span><strong>\s*Reviewed by:?\s*<\/strong>\s*)([^<]+)(<\/span>)/i.test(content)) {
        content = content.replace(
            /(<span><strong>\s*Reviewed by:?\s*<\/strong>\s*)([^<]+)(<\/span>)/gi,
            (m, p1, p2, p3) => {
                if (p2.trim() !== TARGET_REVIEWED_BY) {
                    changes.push(`Editorial Box Reviewed by: "${p2.trim()}" -> "${TARGET_REVIEWED_BY}"`);
                    fileModified = true;
                }
                return `${p1}${TARGET_REVIEWED_BY}${p3}`;
            }
        );
    }

    // 3. Combined paragraph: Written by LHI Verified Author • Reviewed by LHI Technical Reviewer
    if (/Written by\s+[A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+?\s*(?:•|&bull;|\||&nbsp;\|&nbsp;)\s*Reviewed by\s+[A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+(?=<\/p|<\/div|<\/span|\n|\r)/i.test(content)) {
        content = content.replace(
            /Written by\s+[A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+?\s*(?:•|&bull;|\||&nbsp;\|&nbsp;)\s*Reviewed by\s+[A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+(?=<\/p|<\/div|<\/span|\n|\r)/gi,
            (m) => {
                changes.push(`Combined attribution paragraph updated to "${TARGET_WRITTEN_BY} • ${TARGET_REVIEWED_BY}"`);
                fileModified = true;
                return `Written by: ${TARGET_WRITTEN_BY} • Reviewed by: ${TARGET_REVIEWED_BY}`;
            }
        );
    }

    // 4. Standalone <strong>Written by:</strong> ...
    if (/(<strong>\s*Written by:?\s*<\/strong>\s*)([A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+?)(?=(?:<br|\n|\r|<\/p|<\/div|•|&bull;|<strong>\s*Reviewed))/i.test(content)) {
        content = content.replace(
            /(<strong>\s*Written by:?\s*<\/strong>\s*)([A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+?)(?=(?:<br|\n|\r|<\/p|<\/div|•|&bull;|<strong>\s*Reviewed))/gi,
            (m, p1, p2) => {
                if (p2.trim() !== TARGET_WRITTEN_BY) {
                    changes.push(`Standalone Written by: "${p2.trim()}" -> "${TARGET_WRITTEN_BY}"`);
                    fileModified = true;
                }
                return `${p1}${TARGET_WRITTEN_BY} `;
            }
        );
    }

    // 5. Standalone <strong>Reviewed by:</strong> ...
    if (/(<strong>\s*Reviewed by:?\s*<\/strong>\s*)([A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+?)(?=(?:<br|\n|\r|<\/p|<\/div|<\/span))/i.test(content)) {
        content = content.replace(
            /(<strong>\s*Reviewed by:?\s*<\/strong>\s*)([A-Za-z0-9\s,\.\(\)\[\]:;&\-\/]+?)(?=(?:<br|\n|\r|<\/p|<\/div|<\/span))/gi,
            (m, p1, p2) => {
                if (p2.trim() !== TARGET_REVIEWED_BY) {
                    changes.push(`Standalone Reviewed by: "${p2.trim()}" -> "${TARGET_REVIEWED_BY}"`);
                    fileModified = true;
                }
                return `${p1}${TARGET_REVIEWED_BY}`;
            }
        );
    }

    // 6. Icon-based attribution:
    // <span><i class="fa-regular fa-user"...></i> Written by: ...</span>
    if (/(<span><i class="fa-regular fa-user"[^>]*><\/i>\s*Written by:?\s*)([^<]+)(<\/span>)/i.test(content)) {
        content = content.replace(
            /(<span><i class="fa-regular fa-user"[^>]*><\/i>\s*Written by:?\s*)([^<]+)(<\/span>)/gi,
            (m, p1, p2, p3) => {
                if (p2.trim() !== TARGET_WRITTEN_BY) {
                    changes.push(`Icon Written by: "${p2.trim()}" -> "${TARGET_WRITTEN_BY}"`);
                    fileModified = true;
                }
                return `${p1}${TARGET_WRITTEN_BY}${p3}`;
            }
        );
    }
    // <span><i class="fa-regular fa-user-check"...></i> Reviewed by: ...</span>
    if (/(<span><i class="fa-regular fa-user-check"[^>]*><\/i>\s*Reviewed by:?\s*)([^<]+)(<\/span>)/i.test(content)) {
        content = content.replace(
            /(<span><i class="fa-regular fa-user-check"[^>]*><\/i>\s*Reviewed by:?\s*)([^<]+)(<\/span>)/gi,
            (m, p1, p2, p3) => {
                if (p2.trim() !== TARGET_REVIEWED_BY) {
                    changes.push(`Icon Reviewed by: "${p2.trim()}" -> "${TARGET_REVIEWED_BY}"`);
                    fileModified = true;
                }
                return `${p1}${TARGET_REVIEWED_BY}${p3}`;
            }
        );
    }

    // Clean whitespace
    content = content.replace(/LHI Editorial Team\s+•/g, 'LHI Editorial Team •');

    if (fileModified && content !== originalContent) {
        fs.writeFileSync(filePath, content, 'utf8');
        report.articlesUpdated++;
        report.modifiedFiles.push(file);
    }

    report.perFileDetails.push({
        num: dateInfo.num,
        file,
        title: dateInfo.title,
        publicationDate: dateInfo.displayDate,
        isoDate: dateInfo.isoDate,
        year: yr,
        modified: fileModified,
        changes
    });
});

// 2. Standardize blog-data/posts.json
const postsJsonPath = path.join(ROOT_DIR, 'blog-data', 'posts.json');
if (fs.existsSync(postsJsonPath)) {
    try {
        const posts = JSON.parse(fs.readFileSync(postsJsonPath, 'utf8'));
        let postsChanged = false;
        posts.forEach(p => {
            if (p.author && (p.author === 'LHI Design Desk' || p.author === 'LHI Structural Desk' || p.author.includes('Desk'))) {
                p.author = TARGET_WRITTEN_BY;
                postsChanged = true;
            }
        });
        if (postsChanged) {
            fs.writeFileSync(postsJsonPath, JSON.stringify(posts, null, 2), 'utf8');
            report.modifiedFiles.push('blog-data/posts.json');
            console.log('blog-data/posts.json authors standardized to LHI Editorial Team');
        }
    } catch (e) {
        console.error('Error updating blog-data/posts.json:', e);
    }
}

// 3. Align sitemap.xml lastmod dates
const sitemapPath = path.join(ROOT_DIR, 'sitemap.xml');
if (fs.existsSync(sitemapPath)) {
    try {
        let sitemap = fs.readFileSync(sitemapPath, 'utf8');
        let sitemapChanged = false;
        let alignedCount = 0;

        Object.values(verifiedDateMap).forEach(item => {
            const file = item.file;
            const isoDate = item.isoDate;
            const re = new RegExp(`(<loc>https:\\/\\/luxuryhomesofindia\\.in\\/${file.replace('.', '\\.')}<\\/loc>\\s*<lastmod>)([^<]+)(<\\/lastmod>)`);
            if (re.test(sitemap)) {
                sitemap = sitemap.replace(re, (m, p1, p2, p3) => {
                    if (p2 !== isoDate) {
                        alignedCount++;
                        sitemapChanged = true;
                    }
                    return `${p1}${isoDate}${p3}`;
                });
            }
        });

        if (sitemapChanged) {
            fs.writeFileSync(sitemapPath, sitemap, 'utf8');
            report.modifiedFiles.push('sitemap.xml');
            console.log(`sitemap.xml updated: ${alignedCount} lastmod dates aligned with verified dates`);
        }
    } catch (e) {
        console.error('Error updating sitemap.xml:', e);
    }
}

fs.writeFileSync(path.join(ROOT_DIR, 'scratch', 'standardization_audit_report.json'), JSON.stringify(report, null, 2), 'utf8');

console.log('\n================ STANDARDIZATION SUMMARY ================');
console.log(`Total Blog Articles Audited: ${report.totalAudited}`);
console.log(`Articles Updated with Standard Attribution: ${report.articlesUpdated}`);
console.log(`Articles with Verified 2025 Dates: ${report.articles2025}`);
console.log(`Articles with Verified 2026 Dates: ${report.articles2026}`);
console.log(`Articles with Missing/Conflicting Dates: ${report.articlesNeedingReview.length}`);
console.log(`Total Files Modified: ${report.modifiedFiles.length}`);
console.log('Full report saved to scratch/standardization_audit_report.json');
