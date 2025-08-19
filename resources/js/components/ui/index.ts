/**
 * UI Components Export Index
 *
 * Centralized exports for all migrated dub-main UI components
 * Maintains clean import paths throughout the application
 */

// Foundational Components
export { Button } from './button';
export { Input } from './input';
export { Tooltip } from './tooltip';
export { Popover } from './popover';
export { Modal, Dialog } from './modal';
export { LoadingSpinner } from './loading-spinner';
export { AnimatedSizeContainer } from './animated-size-container';
export { ClientOnly } from './client-only';
export { Wordmark } from './wordmark';

// Form Components
export { Label } from './label';
export { Checkbox } from './checkbox';
export { Select } from './select';
export { RadioGroup, RadioGroupItem } from './radio-group';

// Layout Components
export { PageWidthWrapper } from '../layout/page-width-wrapper';

// Data Display Components
export { CardList } from './card-list';
export {
  Card,
  CardHeader,
  CardFooter,
  CardTitle,
  CardAction,
  CardDescription,
  CardContent
} from './card';
export { Badge } from './badge';
export { Avatar, AvatarImage, AvatarFallback } from './avatar';
export { Switch } from './switch';
export { Textarea } from './textarea';
export { Separator } from './separator';

// Dropdown
export {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from './dropdown-menu';

// Navigation Components
export { MainNav } from '../navigation/main-nav';
export { NavButton } from '../navigation/nav-button';
export { UserDropdown } from '../navigation/user-dropdown';
export { WorkspaceDropdown } from '../navigation/workspace-dropdown';

export { Sidebar } from '../navigation/sidebar';

// Protocol Design System Components
export { GridPattern } from '../shared/grid-pattern';
export { ProtocolBackground, ProtocolPage } from '../shared/protocol-background';
