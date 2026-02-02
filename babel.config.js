module.exports = {
  presets: [
    [
      "@babel/preset-env",
      {
        targets: {
          ios: "12",
        },
        useBuiltIns: "entry",
        corejs: 3,
      },
    ],
  ],
};
