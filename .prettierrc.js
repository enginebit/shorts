module.exports = {
  // Basic formatting options
  semi: false,
  singleQuote: true,
  quoteProps: 'as-needed',
  trailingComma: 'all',
  tabWidth: 2,
  useTabs: false,
  printWidth: 100,
  endOfLine: 'lf',

  // JSX specific options
  jsxSingleQuote: true,
  jsxBracketSameLine: false,

  // Plugin configurations
  plugins: [
    'prettier-plugin-organize-imports',
    'prettier-plugin-tailwindcss',
  ],

  // Plugin options
  organizeImportsSkipDestructiveCodeActions: true,
  tailwindConfig: './tailwind.config.js',
  tailwindFunctions: ['clsx', 'cn', 'cva'],

  // File-specific overrides
  overrides: [
    {
      files: '*.json',
      options: {
        printWidth: 80,
        tabWidth: 2,
      },
    },
    {
      files: '*.md',
      options: {
        printWidth: 80,
        proseWrap: 'always',
      },
    },
    {
      files: '*.yml',
      options: {
        tabWidth: 2,
        singleQuote: false,
      },
    },
    {
      files: '*.yaml',
      options: {
        tabWidth: 2,
        singleQuote: false,
      },
    },
  ],
}
