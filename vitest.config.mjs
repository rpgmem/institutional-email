import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		environment: 'jsdom',
		include: [ 'tests/js/**/*.test.js' ],
		// Until the generator lands, there is nothing to test yet.
		passWithNoTests: true,
	},
} );
