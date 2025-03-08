import jsConfig from '@eslint/js'; // Provides `eslint:recommended`
import typescriptPlugin from '@typescript-eslint/eslint-plugin'; // TypeScript plugin
import reactPlugin from 'eslint-plugin-react'; // React plugin
import typescriptParser from '@typescript-eslint/parser';
import globals from "globals";

export default [
    // Base ESLint recommended rules for general JavaScript files
    jsConfig.configs.recommended,

    // General settings and global configurations shared across the project
    {
        settings: {
            react: {
                version: 'detect', // Automatically detect React version
            },
        },
        languageOptions: {
            ecmaVersion: 2023, // Enable modern ECMAScript features
            sourceType: 'module', // Enable ES Module syntax
            parserOptions: {
                ecmaVersion: 2023,
                sourceType: 'module',
                ecmaFeatures: { jsx: true },
            },
            globals: {
                ...globals.browser,
                module: 'writable', // CommonJS compatibility
                require: 'writable',
            },
        },
        ignores: ['node_modules/', 'dist/', 'public/'],
        rules: {
            // General rules shared across project
            'no-console': 'warn',
            eqeqeq: 'error',
            quotes: ['error', 'single'],
            semi: ['error', 'always'],
            indent: ['error', 2],
            curly: 'error',
            'no-else-return': 'error',
            'comma-dangle': ['error', 'only-multiline'],
            'object-curly-spacing': ['error', 'always'],
            'arrow-spacing': ['error', { before: true, after: true }],
        },
    },

    // TypeScript-specific rules targeting `.ts` and `.tsx` files
    {
        files: ['**/*.ts', '**/*.tsx'], // Apply these settings to TypeScript files
        languageOptions: {
            parser: typescriptParser, // Use the TypeScript parser
        },
        plugins: {
            '@typescript-eslint': typescriptPlugin, // Add TypeScript plugin
        },
        rules: {
            ...typescriptPlugin.configs.recommended.rules, // Include TypeScript recommended rules
            '@typescript-eslint/no-unused-expressions': [
                'error',
                {
                    allowShortCircuit: true,
                    allowTernary: true,
                    allowTaggedTemplates: true,
                },
            ],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/explicit-function-return-type': 'warn',
            '@typescript-eslint/consistent-type-imports': [
                'error',
                { prefer: 'type-imports' },
            ],
            '@typescript-eslint/no-unused-vars': 'warn',
            // Disable conflicting JavaScript rules for TypeScript
            'no-unused-vars': 'off',
        },
    },

    // React-specific rules targeting `.jsx` and `.tsx` files
    {
        files: ['**/*.jsx', '**/*.tsx'],
        plugins: {
            react: reactPlugin, // Add React plugin
        },
        settings: {
            react: {
                version: 'detect', // Automatically detect React version
            },
        },
        rules: {
            ...reactPlugin.configs.recommended.rules, // Include React recommended rules
            'react/react-in-jsx-scope': 'off', // Disable for React 17+
            'react/jsx-uses-react': 'off', // Disable for React 17+
            'react/prop-types': 'off', // Disable prop-types checks if using TypeScript
        },
    },
];