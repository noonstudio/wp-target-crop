/*
 * Based upon the Archiver quickstart.
 * @see: https://www.archiverjs.com/docs/quickstart
 */

// Require modules.
const AdmZip = require("adm-zip");
const archiver = require("archiver");
const fs = require("fs");

const args = process.argv.slice(2);
const slug = args[0];

if (slug) {
  // Set the path for the ZIP file.
  const zipFilePath = `../../${slug}.zip`;

  // Create a file to stream archive data to.
  const output = fs.createWriteStream(zipFilePath);
  const archive = archiver("zip");

  // This event is fired when the data source is drained no matter what was the data source.
  // It is not part of this library but rather from the NodeJS Stream API.
  // @see: https://nodejs.org/api/stream.html#stream_event_end
  output.on("end", function () {
    console.log("Data has been drained");
  });

  // Catch warnings.
  archive.on("warning", function (err) {
    if (err.code === "ENOENT") {
      // log warning
    } else {
      // throw error
      throw err;
    }
  });

  // Catch errors.
  archive.on("error", function (err) {
    throw err;
  });

  // Pipe archive data to the file.
  archive.pipe(output);

  // Append the entire contents of the theme directory to a directory with
  // the theme slug.

  const excludedItems = [
    "src/**",
    "node_modules/**",
    "package.json",
    "postcss.config.js",
    "tailwind.config.js",
    "composer.lock",
    "composer.phar",
    "composer-setup.php",
    "*.zip",
    "*.log",
    "*.md",
    "*.sh",
    "*.yml",
    "*.lock",
  ];
  // List of directories and files to exclude
  archive.glob(
    "**",
    {
      cwd: `./`,
      ignore: excludedItems,
    },
    { prefix: slug }
  );

  // Finalize the archive.
  archive.finalize();
}
