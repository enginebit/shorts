/**
 * CardList System Export
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/card-list/index.ts
 * 
 * Provides the complete CardList system with:
 * - CardList component with variants (compact, loose)
 * - CardList.Card component with hover states
 * - CardList.Context and CardList.Card.Context for state management
 */

import { CardList as CardListComponent, CardListContext } from './card-list';
import { CardContext, CardListCard } from './card-list-card';

const CardList = Object.assign(CardListComponent, {
  Card: Object.assign(CardListCard, {
    Context: CardContext,
  }),
  Context: CardListContext,
});

export { CardList };
