import React, { useState } from 'react';
import { Button, Input } from '@/components/ui';
import { Filter, Plus, Search } from 'lucide-react';
import { useLinkBuilder } from '@/contexts/modal-context';

export interface LinksHeaderProps {
  totalLinks: number;
  onSearch: (query: string) => void;
}

const LinksHeader = React.memo(function LinksHeader({ totalLinks, onSearch }: LinksHeaderProps) {
  const { setShowLinkBuilder } = useLinkBuilder();
  const [searchQuery, setSearchQuery] = useState('');

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSearch(searchQuery);
  };

  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 className="text-2xl font-bold text-neutral-900">Links</h1>
        <p className="text-sm text-neutral-600">
          {totalLinks} {totalLinks === 1 ? 'link' : 'links'} in this workspace
        </p>
      </div>

      <div className="flex items-center gap-3">
        {/* Search */}
        <form onSubmit={handleSearchSubmit} className="relative">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
          <Input
            type="text"
            placeholder="Search links..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-10 w-64"
          />
        </form>

        {/* Filter Button */}
        <Button variant="secondary" size="sm">
          <Filter className="h-4 w-4 mr-2" />
          Filter
        </Button>

        {/* Create Link Button */}
        <Button onClick={() => setShowLinkBuilder(true)}>
          <Plus className="h-4 w-4 mr-2" />
          Create Link
        </Button>
      </div>
    </div>
  );
});

export default LinksHeader;

