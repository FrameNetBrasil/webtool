module.exports = {
    env: {
        node: true,
        "vue/setup-compiler-macros": true
    },
    parser: "vue-eslint-parser",
    parserOptions: {
        parser: "@typescript-eslint/parser"
    },
    plugins: [
        "@typescript-eslint"
    ],
    extends: [
        "eslint:recommended",
        "plugin:@typescript-eslint/eslint-recommended",
        "plugin:@typescript-eslint/recommended",
        "plugin:vue/vue3-recommended",
        "prettier"
    ],
    rules: {
        // override/add rules settings here, such as:
        // 'vue/no-unused-vars': 'error'
        "vue/script-setup-uses-vars": 1,
        "@typescript-eslint/no-explicit-any": 0,
        "vue/no-v-html": 0
    }
};
