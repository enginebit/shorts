/**
 * Link Preview Component
 *
 * Dub.co Reference: /apps/web/ui/modals/link-builder/ (preview patterns)
 *
 * Key Patterns Adopted:
 * - Real-time link preview with metadata
 * - URL validation and favicon fetching
 * - Social media preview cards
 * - QR code generation for links
 *
 * Adaptations for Laravel + Inertia.js:
 * - Simplified for initial implementation
 * - Uses our UI components
 * - Maintains exact visual consistency
 * - Integrated with our form handling system
 */

import { useState, useEffect } from 'react';
import { ExternalLink, Copy, QrCode, Globe, Image, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button, LoadingSpinner } from '@/components/ui';
import { toast } from 'sonner';

interface LinkMetadata {
  title?: string;
  description?: string;
  image?: string;
  favicon?: string;
  domain?: string;
}

interface LinkPreviewProps {
  url: string;
  shortLink: string;
  title?: string;
  description?: string;
  image?: string;
  className?: string;
}

export function LinkPreview({
  url,
  shortLink,
  title,
  description,
  image,
  className,
}: LinkPreviewProps) {
  const [metadata, setMetadata] = useState<LinkMetadata | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Fetch metadata for the URL
  useEffect(() => {
    if (!url || !isValidUrl(url)) {
      setMetadata(null);
      setError(null);
      return;
    }

    const fetchMetadata = async () => {
      try {
        setLoading(true);
        setError(null);

        // In a real implementation, this would call a metadata service
        // For now, we'll extract basic info from the URL
        const urlObj = new URL(url);
        const domain = urlObj.hostname.replace('www.', '');
        
        setMetadata({
          title: title || `Link to ${domain}`,
          description: description || `Visit ${url}`,
          image: image,
          favicon: `https://www.google.com/s2/favicons?domain=${domain}&sz=32`,
          domain,
        });
      } catch (err) {
        setError('Failed to load link preview');
        setMetadata(null);
      } finally {
        setLoading(false);
      }
    };

    const debounceTimer = setTimeout(fetchMetadata, 500);
    return () => clearTimeout(debounceTimer);
  }, [url, title, description, image]);

  const handleCopyShortLink = async () => {
    try {
      await navigator.clipboard.writeText(shortLink);
      toast.success('Short link copied to clipboard');
    } catch (error) {
      toast.error('Failed to copy link');
    }
  };

  const handleCopyLongLink = async () => {
    try {
      await navigator.clipboard.writeText(url);
      toast.success('Original link copied to clipboard');
    } catch (error) {
      toast.error('Failed to copy link');
    }
  };

  if (!url) {
    return (
      <div className={cn('rounded-lg border border-neutral-200 p-6', className)}>
        <div className="text-center text-neutral-500">
          <Globe className="mx-auto h-8 w-8 mb-2" />
          <p className="text-sm">Enter a URL to see the preview</p>
        </div>
      </div>
    );
  }

  if (!isValidUrl(url)) {
    return (
      <div className={cn('rounded-lg border border-red-200 bg-red-50 p-6', className)}>
        <div className="text-center text-red-600">
          <AlertCircle className="mx-auto h-8 w-8 mb-2" />
          <p className="text-sm">Please enter a valid URL</p>
        </div>
      </div>
    );
  }

  return (
    <div className={cn('space-y-4', className)}>
      {/* Short Link Preview */}
      <div className="rounded-lg border border-neutral-200 bg-white p-4">
        <div className="flex items-center justify-between">
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium text-neutral-900">Short Link</p>
            <p className="text-sm text-blue-600 truncate">{shortLink}</p>
          </div>
          <div className="flex items-center gap-2 ml-4">
            <Button
              variant="ghost"
              size="sm"
              onClick={handleCopyShortLink}
              className="h-8 w-8 p-0"
            >
              <Copy className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => window.open(shortLink, '_blank')}
              className="h-8 w-8 p-0"
            >
              <ExternalLink className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      {/* Destination Preview */}
      <div className="rounded-lg border border-neutral-200 bg-white overflow-hidden">
        {loading ? (
          <div className="p-6 text-center">
            <LoadingSpinner className="mx-auto mb-2" />
            <p className="text-sm text-neutral-500">Loading preview...</p>
          </div>
        ) : error ? (
          <div className="p-6 text-center text-red-600">
            <AlertCircle className="mx-auto h-8 w-8 mb-2" />
            <p className="text-sm">{error}</p>
          </div>
        ) : metadata ? (
          <div className="p-4">
            <div className="flex items-start gap-4">
              {/* Favicon */}
              <div className="flex-shrink-0">
                {metadata.favicon ? (
                  <img
                    src={metadata.favicon}
                    alt=""
                    className="h-8 w-8 rounded"
                    onError={(e) => {
                      (e.target as HTMLImageElement).style.display = 'none';
                    }}
                  />
                ) : (
                  <div className="h-8 w-8 rounded bg-neutral-100 flex items-center justify-center">
                    <Globe className="h-4 w-4 text-neutral-400" />
                  </div>
                )}
              </div>

              {/* Content */}
              <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between">
                  <div className="min-w-0 flex-1">
                    <h3 className="text-sm font-medium text-neutral-900 truncate">
                      {metadata.title}
                    </h3>
                    <p className="text-xs text-neutral-500 truncate">
                      {metadata.domain}
                    </p>
                  </div>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={handleCopyLongLink}
                    className="h-8 w-8 p-0 ml-2"
                  >
                    <Copy className="h-4 w-4" />
                  </Button>
                </div>
                
                {metadata.description && (
                  <p className="text-sm text-neutral-600 mt-2 line-clamp-2">
                    {metadata.description}
                  </p>
                )}
              </div>

              {/* Preview Image */}
              {metadata.image && (
                <div className="flex-shrink-0">
                  <img
                    src={metadata.image}
                    alt=""
                    className="h-16 w-16 rounded object-cover"
                    onError={(e) => {
                      (e.target as HTMLImageElement).style.display = 'none';
                    }}
                  />
                </div>
              )}
            </div>
          </div>
        ) : null}
      </div>

      {/* QR Code Section */}
      <div className="rounded-lg border border-neutral-200 bg-white p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-neutral-900">QR Code</p>
            <p className="text-xs text-neutral-500">Generate QR code for easy sharing</p>
          </div>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              // TODO: Implement QR code generation
              toast.info('QR code generation coming soon');
            }}
          >
            <QrCode className="h-4 w-4 mr-2" />
            Generate
          </Button>
        </div>
      </div>
    </div>
  );
}

// Utility function to validate URLs
function isValidUrl(string: string): boolean {
  try {
    new URL(string);
    return true;
  } catch {
    return false;
  }
}
