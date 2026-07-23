import React, { useMemo } from 'react';

/**
 * Parses AI interpretation markdown into structured cards.
 * Splits by ## headings into sections with icons and colors.
 */
function parseSections(text) {
    if (!text) return [];

    const sections = [];
    const headingRegex = /^## (.+)$/gm;
    const matches = [];
    let match;

    while ((match = headingRegex.exec(text)) !== null) {
        matches.push({ title: match[1].trim(), index: match.index });
    }

    // No sections found — return entire text as a single Insight card
    if (matches.length === 0) {
        const cleaned = text.replace(/^#+\s*.*$/gm, '').trim();
        if (cleaned) {
            sections.push({
                title: 'AI Insight',
                icon: '◉',
                color: 'teal',
                bgColor: 'bg-teal-50',
                borderColor: 'border-teal-200',
                content: text.trim(),
            });
        }
        return sections;
    }

    // Extract content between headings
    for (let i = 0; i < matches.length; i++) {
        const start = matches[i].index + matches[i].title.length + 3;
        const end = i < matches.length - 1 ? matches[i + 1].index : text.length;
        const rawContent = text.slice(start, end).trim();
        const headingTitle = matches[i].title;
        const titleLower = headingTitle.toLowerCase();

        let section;
        if (titleLower.includes('key finding') || titleLower.includes('summary') || titleLower.includes('overview') || titleLower.includes('abnormal')) {
            section = {
                title: headingTitle,
                icon: '★',
                color: 'warning',
                bgColor: 'bg-warning-50',
                borderColor: 'border-warning-200',
                content: rawContent,
            };
        } else if (titleLower.includes('simple explanation') || titleLower.includes('explanation') || titleLower.includes('what this means')) {
            section = {
                title: 'Explanation',
                icon: '⬡',
                color: 'info',
                bgColor: 'bg-info-50',
                borderColor: 'border-info-200',
                content: rawContent,
            };
        } else if (
            titleLower.includes('what to do') || titleLower.includes('recommend') ||
            titleLower.includes('next step') || titleLower.includes('action') || titleLower.includes('follow-up')
        ) {
            section = {
                title: headingTitle,
                icon: '→',
                color: 'success',
                bgColor: 'bg-success-50',
                borderColor: 'border-success-200',
                content: rawContent,
            };
        } else if (titleLower.includes('normal')) {
            section = {
                title: headingTitle,
                icon: '✓',
                color: 'success',
                bgColor: 'bg-success-50',
                borderColor: 'border-success-200',
                content: rawContent,
            };
        } else if (titleLower.includes('disclaimer')) {
            section = {
                title: headingTitle,
                icon: 'ℹ',
                color: 'neutral',
                bgColor: 'bg-neutral-50',
                borderColor: 'border-neutral-200',
                content: rawContent,
            };
        } else {
            section = {
                title: headingTitle,
                icon: '◉',
                color: 'teal',
                bgColor: 'bg-teal-50',
                borderColor: 'border-teal-200',
                content: rawContent,
            };
        }

        sections.push(section);
    }

    return sections;
}

/**
 * Renders simple markdown-like text (bold, lists, paragraphs) as HTML.
 */
function renderMarkdown(content) {
    if (!content) return '';

    let html = content
        // Bold
        .replace(/\*\*(.+?)\*\*/g, '<strong class="font-bold text-neutral-900">$1</strong>')
        // Italic
        .replace(/\*(.+?)\*/g, '<em class="italic text-neutral-600">$1</em>')
        // Inline code
        .replace(/`(.+?)`/g, '<code class="bg-neutral-100 rounded px-1 py-0.5 text-xs text-danger-700 font-mono">$1</code>')
        // Blockquote
        .replace(/^> (.+)$/gm, '<blockquote class="border-l-3 border-teal-300 pl-3 my-2 bg-neutral-50 rounded-r-lg p-3 text-sm text-neutral-600 italic">$1</blockquote>')
        // Unordered list items
        .replace(/^- (.+)$/gm, '<li class="flex gap-2 ml-0"><span class="text-neutral-400">·</span><span>$1</span></li>')
        // Ordered list items
        .replace(/^\d+\. (.+)$/gm, '<li class="ml-0">$1</li>')
        // Double newline → paragraph break
        .replace(/\n\n/g, '</p><p class="mb-2 leading-relaxed text-sm text-neutral-700">')
        // Single newline → line break
        .replace(/\n/g, '<br />');

    // Wrap list items in <ul> or <ol>
    html = html.replace(/((?:<li[^>]*>.*?<\/li>\s*)+)/g, (match) => {
        if (match.includes('flex gap-2')) {
            return `<ul class="space-y-1 mb-2 list-none">${match}</ul>`;
        }
        return `<ol class="space-y-1 mb-2 list-decimal pl-4">${match}</ol>`;
    });

    return `<p class="mb-2 leading-relaxed text-sm text-neutral-700">${html}</p>`;
}

const colorMap = {
    teal: { iconBg: 'bg-teal-50 text-teal-600', heading: 'text-teal-800' },
    warning: { iconBg: 'bg-warning-50 text-warning-600', heading: 'text-warning-800' },
    info: { iconBg: 'bg-info-50 text-info-600', heading: 'text-info-800' },
    success: { iconBg: 'bg-success-50 text-success-600', heading: 'text-success-800' },
    neutral: { iconBg: 'bg-neutral-100 text-neutral-600', heading: 'text-neutral-800' },
};

export default function InterpretationCards({ markdownText }) {
    const sections = useMemo(() => parseSections(markdownText), [markdownText]);

    if (!sections.length) return null;

    return (
        <div className="space-y-3">
            {sections.map((section, i) => {
                const cm = colorMap[section.color] || colorMap.teal;
                return (
                    <div key={i} className={`card p-4 ${section.bgColor} border ${section.borderColor}`}>
                        <div className="flex items-center gap-2.5 mb-3 pb-2.5 border-b border-neutral-200/50">
                            <span className={`w-8 h-8 rounded-lg flex items-center justify-center text-base ${cm.iconBg}`}>
                                {section.icon}
                            </span>
                            <span className={`text-sm font-bold ${cm.heading}`}>{section.title}</span>
                        </div>
                        <div
                            className="text-sm text-neutral-700"
                            dangerouslySetInnerHTML={{ __html: renderMarkdown(section.content) }}
                        />
                    </div>
                );
            })}
        </div>
    );
}