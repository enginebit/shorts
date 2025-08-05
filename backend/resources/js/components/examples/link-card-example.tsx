/**
 * LinkCard Example Component
 * 
 * Demonstrates the CardList system with link data
 * Based on: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/links/link-card.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced useRouter with Inertia navigation
 * - Simplified for demonstration purposes
 * - Maintains exact two-column layout pattern
 */

import { Link as InertiaLink } from '@inertiajs/react';
import { CardList } from '@/components/ui/card-list';
import { cn, formatNumber, formatRelativeTime } from '@/lib/utils';
import { Link } from '@/types';
import { ExternalLink, Copy, BarChart3 } from 'lucide-react';
import { useState } from 'react';

interface LinkCardProps {
  link: Link;
}

export function LinkCardExample({ link }: LinkCardProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = async (e: React.MouseEvent) => {
    e.stopPropagation();
    const shortUrl = `${link.domain}/${link.key}`;
    
    try {
      await navigator.clipboard.writeText(`https://${shortUrl}`);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error('Failed to copy:', error);
    }
  };

  const handleCardClick = () => {
    // In a real app, this would navigate to the link edit page
    console.log('Navigate to link edit:', link.id);
  };

  return (
    <CardList.Card
      onClick={handleCardClick}
      outerClassName="overflow-hidden"
      innerClassName="p-0"
    >
      {/* Two-column layout following dub-main pattern */}
      <div className="flex items-center gap-5 px-4 py-2.5 text-sm sm:gap-8 md:gap-12">
        {/* Title Column - Left side */}
        <div className="min-w-0 grow">
          <LinkTitleColumn link={link} onCopy={handleCopy} copied={copied} />
        </div>
        
        {/* Details Column - Right side */}
        <LinkDetailsColumn link={link} />
      </div>
    </CardList.Card>
  );
}

function LinkTitleColumn({ 
  link, 
  onCopy, 
  copied 
}: { 
  link: Link; 
  onCopy: (e: React.MouseEvent) => void;
  copied: boolean;
}) {
  return (
    <div className="flex items-center gap-2">
      {/* Link Icon/Favicon */}
      <div className="flex size-8 items-center justify-center rounded-full bg-neutral-100">
        <ExternalLink className="size-4 text-neutral-600" />
      </div>
      
      <div className="min-w-0 flex-1">
        {/* Short URL */}
        <div className="flex items-center gap-2">
          <p className="truncate font-medium text-neutral-900">
            {link.domain}/{link.key}
          </p>
          <button
            onClick={onCopy}
            className={cn(
              'rounded p-1 transition-colors hover:bg-neutral-100',
              copied && 'bg-green-100 text-green-600'
            )}
            title={copied ? 'Copied!' : 'Copy link'}
          >
            <Copy className="size-3" />
          </button>
        </div>
        
        {/* Destination URL */}
        <p className="truncate text-xs text-neutral-500" title={link.url}>
          {link.url}
        </p>
        
        {/* Title if available */}
        {link.title && (
          <p className="truncate text-xs text-neutral-600 mt-0.5" title={link.title}>
            {link.title}
          </p>
        )}
      </div>
    </div>
  );
}

function LinkDetailsColumn({ link }: { link: Link }) {
  return (
    <div className="flex items-center justify-end gap-2 sm:gap-5">
      {/* Click Stats */}
      <div className="flex items-center gap-1 text-neutral-600">
        <BarChart3 className="size-3" />
        <span className="text-xs font-medium">
          {formatNumber(link.clicks)}
        </span>
      </div>
      
      {/* Created Date */}
      <div className="hidden text-xs text-neutral-500 sm:block">
        {formatRelativeTime(link.created_at)}
      </div>
      
      {/* Status Indicator */}
      <div className={cn(
        'size-2 rounded-full',
        link.expires_at && new Date(link.expires_at) < new Date()
          ? 'bg-red-400'
          : 'bg-green-400'
      )} />
    </div>
  );
}

// Example data generator for demonstration
export function generateExampleLinks(count: number = 5): Link[] {
  const domains = ['dub.sh', 'short.ly', 'link.co'];
  const urls = [
    'https://github.com/dubinc/dub',
    'https://dub.co/blog/introducing-dub',
    'https://twitter.com/dubdotco',
    'https://dub.co/pricing',
    'https://docs.dub.co',
  ];
  
  return Array.from({ length: count }, (_, i) => ({
    id: `link-${i + 1}`,
    project_id: 'project-1',
    domain: domains[i % domains.length],
    key: `link${i + 1}`,
    url: urls[i % urls.length],
    title: `Example Link ${i + 1}`,
    description: `This is an example link description for link ${i + 1}`,
    clicks: Math.floor(Math.random() * 10000),
    unique_clicks: Math.floor(Math.random() * 5000),
    created_at: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date().toISOString(),
  }));
}
