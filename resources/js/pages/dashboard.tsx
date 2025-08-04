/**
 * Dashboard Page Component
 * 
 * Simple dashboard page for authenticated users
 * Will be enhanced with full dashboard functionality later
 */

import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui';
import { PageProps } from '@/types';

export default function Dashboard({ auth }: PageProps) {
  const { post, processing } = useForm();

  const logout = () => {
    post(route('logout'));
  };

  return (
    <AppLayout>
      <Head title="Dashboard" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h1 className="text-3xl font-bold text-gray-900">
                    Welcome back, {auth.user.name}!
                  </h1>
                  <p className="text-gray-600 mt-2">
                    You're successfully logged in to your Shorts account.
                  </p>
                </div>
                
                <Button
                  variant="secondary"
                  text="Logout"
                  loading={processing}
                  onClick={logout}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
                  <h3 className="font-semibold text-blue-900 mb-2">
                    🔗 Links Created
                  </h3>
                  <p className="text-2xl font-bold text-blue-600">0</p>
                  <p className="text-sm text-blue-600 mt-1">
                    Start creating short links
                  </p>
                </div>

                <div className="bg-green-50 border border-green-200 rounded-lg p-6">
                  <h3 className="font-semibold text-green-900 mb-2">
                    📊 Total Clicks
                  </h3>
                  <p className="text-2xl font-bold text-green-600">0</p>
                  <p className="text-sm text-green-600 mt-1">
                    Track your link performance
                  </p>
                </div>

                <div className="bg-purple-50 border border-purple-200 rounded-lg p-6">
                  <h3 className="font-semibold text-purple-900 mb-2">
                    🌐 Domains
                  </h3>
                  <p className="text-2xl font-bold text-purple-600">0</p>
                  <p className="text-sm text-purple-600 mt-1">
                    Add custom domains
                  </p>
                </div>
              </div>

              <div className="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 className="font-semibold text-gray-900 mb-4">
                  🎉 Authentication System Complete!
                </h3>
                <div className="space-y-2 text-sm text-gray-700">
                  <p>✅ Login and registration working</p>
                  <p>✅ Password reset functionality</p>
                  <p>✅ Form validation and error handling</p>
                  <p>✅ Laravel Sanctum integration</p>
                  <p>✅ Inertia.js authentication flow</p>
                </div>
                
                <div className="mt-4 flex gap-2">
                  <Link href={route('login')}>
                    <Button variant="outline" text="View Login Page" />
                  </Link>
                  <Link href={route('register')}>
                    <Button variant="outline" text="View Register Page" />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
