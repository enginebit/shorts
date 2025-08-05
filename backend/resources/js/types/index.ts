// Core model interfaces following dub-main patterns and our Laravel models

export interface User {
  id: string;
  name: string;
  email: string;
  email_verified_at?: string;
  avatar?: string;
  created_at: string;
  updated_at: string;
}

export interface Project {
  id: string;
  name: string;
  slug: string;
  logo?: string;
  plan: 'free' | 'starter' | 'pro' | 'business' | 'enterprise';
  usage: number;
  usage_limit: number;
  links_usage: number;
  links_limit: number;
  ai_usage: number;
  billing_cycle_start: number;
  monthly_clicks: number;
  current_month: string;
  active_links: number;
  total_clicks: number;
  total_links: number;
  stripe_customer_id?: string;
  stripe_subscription_id?: string;
  billing_enabled: boolean;
  trial_ends_at?: string;
  payment_failed_at?: string;
  created_at: string;
  updated_at: string;
  users?: User[];
  domains?: Domain[];
  links?: Link[];
}

export interface Link {
  id: string;
  project_id: string;
  domain: string;
  key: string;
  url: string;
  title?: string;
  description?: string;
  image?: string;
  clicks: number;
  unique_clicks: number;
  last_clicked?: string;
  expires_at?: string;
  password?: string;
  ios_targeting?: string;
  android_targeting?: string;
  geo_targeting?: string;
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
  created_at: string;
  updated_at: string;
  project?: Project;
}

export interface Domain {
  id: string;
  project_id: string;
  domain: string;
  verified: boolean;
  verification_token?: string;
  dns_records?: DnsRecord[];
  created_at: string;
  updated_at: string;
  project?: Project;
}

export interface DnsRecord {
  type: 'A' | 'CNAME' | 'TXT';
  name: string;
  value: string;
  ttl?: number;
}

export interface Invoice {
  id: string;
  project_id: string;
  stripe_invoice_id: string;
  stripe_customer_id: string;
  stripe_subscription_id?: string;
  number?: string;
  status: 'draft' | 'open' | 'paid' | 'void' | 'uncollectible';
  amount_due: number;
  amount_paid: number;
  amount_remaining: number;
  currency: string;
  invoice_date: string;
  due_date?: string;
  paid_at?: string;
  voided_at?: string;
  period_start?: string;
  period_end?: string;
  description?: string;
  line_items?: any[];
  hosted_invoice_url?: string;
  invoice_pdf?: string;
  created_at: string;
  updated_at: string;
}

// Analytics interfaces following dub-main patterns
export interface AnalyticsData {
  clicks: number;
  unique_clicks: number;
  leads: number;
  sales: number;
  amount: number;
  date?: string;
  country?: string;
  city?: string;
  device?: string;
  browser?: string;
  os?: string;
  referer?: string;
}

export interface TimeSeriesData {
  date: string;
  clicks: number;
  unique_clicks: number;
  leads: number;
  sales: number;
  amount: number;
}

export interface TopCountry {
  country: string;
  clicks: number;
  unique_clicks: number;
}

export interface TopReferrer {
  referer: string;
  clicks: number;
  unique_clicks: number;
}

export interface DeviceStats {
  device: string;
  clicks: number;
  unique_clicks: number;
}

// Billing interfaces
export interface UsageStats {
  plan: string;
  billing_cycle_start: number;
  usage: {
    links: {
      used: number;
      limit: number;
      percentage: number;
    };
    clicks: {
      used: number;
      limit: number;
      percentage: number;
    };
    domains: {
      used: number;
      limit: number;
      percentage: number;
    };
    users: {
      used: number;
      limit: number;
      percentage: number;
    };
    ai: {
      used: number;
      limit: number;
      percentage: number;
    };
  };
  overage: Record<string, {
    amount: number;
    rate: number;
    charge: number;
  }>;
}

export interface Plan {
  id: string;
  name: string;
  price: number;
  interval: 'month' | 'year';
  stripe_price_id?: string;
  features: string[];
  limits: {
    links: number;
    clicks: number;
    domains: number;
    users: number;
    tags: number;
    folders: number;
    ai: number;
    payouts: number;
  };
}

// API response interfaces
export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

// Form interfaces
export interface CreateLinkData {
  url: string;
  domain?: string;
  key?: string;
  title?: string;
  description?: string;
  expires_at?: string;
  password?: string;
  ios_targeting?: string;
  android_targeting?: string;
  geo_targeting?: string[];
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
}

export interface CreateDomainData {
  domain: string;
}

export interface CreateProjectData {
  name: string;
  slug: string;
  logo?: string;
}

// Inertia.js page props interface
export interface PageProps {
  auth: {
    user: User;
  };
  flash: {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
  };
  errors: Record<string, string>;
}
