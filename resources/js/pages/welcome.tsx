/**
 * Welcome Page
 * 
 * Test page to verify our React + Inertia.js setup
 * Demonstrates migrated components from dub-main
 */

import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CardList } from '@/components/ui/card-list';
import { LinkCardExample, generateExampleLinks } from '@/components/examples/link-card-example';
import { PageProps } from '@/types';

export default function Welcome({ auth }: PageProps) {
  const exampleLinks = generateExampleLinks(5);

  return (
    <AppLayout>
      <Head title="Welcome" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6 text-neutral-900">
              <h1 className="text-3xl font-bold text-neutral-900 mb-6">
                🎉 Phase 2: Frontend Migration Started!
              </h1>
              
              <div className="space-y-6">
                <div>
                  <h2 className="text-xl font-semibold mb-4">
                    ✅ Infrastructure Setup Complete
                  </h2>
                  <ul className="list-disc list-inside space-y-2 text-neutral-700">
                    <li>React + TypeScript + Inertia.js configured</li>
                    <li>Tailwind CSS with dub-main compatible styling</li>
                    <li>Utility functions and type definitions created</li>
                    <li>Inertia.js middleware configured in Laravel</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-xl font-semibold mb-4">
                    🎯 Migrated Components from Dub-Main
                  </h2>

                  <div className="space-y-6">
                    <div>
                      <h3 className="font-medium mb-2">Button Component</h3>
                      <div className="flex gap-2 flex-wrap">
                        <Button text="Primary Button" variant="primary" />
                        <Button text="Secondary Button" variant="secondary" />
                        <Button text="Outline Button" variant="outline" />
                        <Button text="Success Button" variant="success" />
                        <Button text="Danger Button" variant="danger" />
                        <Button text="Loading..." variant="primary" loading />
                      </div>
                    </div>

                    <div>
                      <h3 className="font-medium mb-2">Input Component</h3>
                      <div className="space-y-2 max-w-md">
                        <Input placeholder="Regular input" />
                        <Input type="password" placeholder="Password input" />
                        <Input placeholder="Input with error" error="This field is required" />
                      </div>
                    </div>

                    <div>
                      <h3 className="font-medium mb-2">CardList System - Loose Variant</h3>
                      <div className="max-w-4xl">
                        <CardList variant="loose">
                          {exampleLinks.map((link) => (
                            <LinkCardExample key={link.id} link={link} />
                          ))}
                        </CardList>
                      </div>
                    </div>

                    <div>
                      <h3 className="font-medium mb-2">CardList System - Compact Variant</h3>
                      <div className="max-w-4xl">
                        <CardList variant="compact">
                          {exampleLinks.slice(0, 3).map((link) => (
                            <LinkCardExample key={link.id} link={link} />
                          ))}
                        </CardList>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h2 className="text-xl font-semibold mb-4">
                    🚀 Next Steps
                  </h2>
                  <ul className="list-disc list-inside space-y-2 text-neutral-700">
                    <li>✅ CardList system migrated and working</li>
                    <li>Create authentication pages (login, register)</li>
                    <li>Build main dashboard navigation</li>
                    <li>Implement link management interface</li>
                    <li>Add analytics and billing components</li>
                  </ul>
                </div>

                {auth.user && (
                  <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 className="font-medium text-green-800 mb-2">
                      👋 Welcome back, {auth.user.name}!
                    </h3>
                    <p className="text-green-700">
                      You're logged in and ready to use the application.
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
