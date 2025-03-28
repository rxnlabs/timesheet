// convert PostCSS to ES6
// usally the postcss.config.js file uses CommonJS
// you can use a postcss.config.mjs file to use ES6 modules
// @see https://github.com/algolia/autocomplete/blob/next/postcss.config.mjs 
import scss from "postcss-scss";
import sass from "@csstools/postcss-sass";
import autoprefixer from "autoprefixer";
import cssnano from "cssnano";
import postCSSImport from "postcss-import";
import tailwindcss from "@tailwindcss/postcss";
import postCSSNested from "postcss-nested";

// use the context capability to get the node NODE_ENV variable to run the minify plugin when the environment is production
// convert the default export to a function that is passed the ctx/context variable
// https://github.com/postcss/postcss-cli/issues/112#issuecomment-287756568
// https://github.com/postcss/postcss-cli?tab=readme-ov-file#context
export default ((ctx) => {
    return {
        map: true,
        syntax: scss,
        parser: scss,
        plugins: [
            //sass({}),
            //postCSSImport({}), // Not needed with version 4.0 of Tailwind
            postCSSNested({}), // Nested CSS rules closer to how SASS nesting works instead of how the CSS draft works
            tailwindcss({}),
            autoprefixer({}),
            ctx.env === 'production' ? cssnano({}) : ''
        ]
    }
});
