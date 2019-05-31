const Promise = require('bluebird');

const AdmZip = require('adm-zip');
const copy = Promise.promisify(require('copy'));
const fs = require('fs-extra');
const moment = require('moment');
const opn = require('opn');
const path = require('path');

const { upgrade } = require('minimist')(process.argv.slice(2));

function formatNumber(number, padding = 2) {
  return number.toString().padStart(padding, '0');
}

async function release() {
  try {
    const baseFolder = path.resolve(__dirname, '..');
    const versionPath = path.resolve(__dirname, '../version.php');

    if (upgrade) {
      // Get current version number
      const fileContent = (await fs.readFile(versionPath)).toString();
      const curPluginVersionMatch = fileContent.match(
        /^\$plugin->version\s+=\s+(\d{8})(\d{2});$/m
      );
      const curPluginDate = curPluginVersionMatch[1];
      const curPluginNumber = Number(curPluginVersionMatch[2]);

      const newPluginDate = moment().format('YYYYMMDD');
      // If it's the same date, increase the version number. Otherwise, set it to 0.
      const newPluginNumber = `${
        curPluginDate === newPluginDate ? curPluginNumber + 1 : 0
      }`.padStart(2, '0');

      const newPluginVersion = `${newPluginDate}${newPluginNumber}`;

      // Write new version
      const newFileContent = fileContent.replace(
        curPluginVersionMatch[0],
        `$plugin->version = ${newPluginVersion};`
      );
      await fs.writeFile(versionPath, newFileContent);
    }

    const tempFolder = path.resolve(baseFolder, 'temp');

    //Copy files to output folder
    console.log(`Coying files to temp folder ${tempFolder}...`);
    const tempWooclapFolder = path.resolve(tempFolder, 'wooclap');
    await copy(path.resolve(baseFolder, '**/*.php'), tempWooclapFolder);
    await copy(path.resolve(baseFolder, '**/*.png'), tempWooclapFolder);
    await copy(path.resolve(baseFolder, '**/*.svg'), tempWooclapFolder);
    await copy(path.resolve(baseFolder, '**/*.xml'), tempWooclapFolder);

    // Remove unnecessary folders
    await fs.remove(path.resolve(tempWooclapFolder, 'node_modules'));
    await fs.remove(path.resolve(tempWooclapFolder, 'vendor'));

    //Zip folder
    const zip = new AdmZip();
    await zip.addLocalFolder(tempFolder);

    const date = new Date();
    const filename = `wooclap-${formatNumber(
      date.getFullYear(),
      4
    )}${formatNumber(date.getMonth() + 1)}${formatNumber(
      date.getDate()
    )}-${formatNumber(date.getHours())}${formatNumber(date.getMinutes())}`;
    const zipFilename = `${filename}.zip`;
    console.log(`Zipping to ${zipFilename}...`);
    await zip.writeZip(path.resolve(baseFolder, zipFilename));

    //Remove temp folder
    console.log('Removing temp folder');
    await fs.remove(tempFolder);

    opn(baseFolder);

    console.log('All done!');
  } catch (err) {
    console.error(err);
  }
}

release();
