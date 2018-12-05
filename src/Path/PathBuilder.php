<?php /** @noinspection ALL */

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace ArchitectureSniffer\Path;

use ArchitectureSniffer\Path\Transfer\PathTransfer;

class PathBuilder implements PathBuilderInterface
{
    protected const CONST_NAME_APPLICATION_ROOT_DIR = 'APPLICATION_ROOT_DIR';

    protected const PATTERN_PATH_MODULE_SCHEMA_FOLDER = 'Persistence/Propel/Schema';

    /**
     * @param string $filePath
     *
     * @return \ArchitectureSniffer\Path\Transfer\PathTransfer
     */
    public function getPath(string $filePath): PathTransfer
    {
        $rootPath = $this->getRootApplicationDirectoryPathByFilePath($filePath);
        $corePath = $this->getCorePath($filePath);
        $projectPath = $this->getProjectPath($filePath);

        $pathTransfer = new PathTransfer();

        $pathTransfer->setRootPath($rootPath);
        $pathTransfer->setCorePath($corePath);
        $pathTransfer->setProjectPath($projectPath);

        return $pathTransfer;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function getRootApplicationDirectoryPathByFilePath(string $filePath): string
    {
        if ($this->isApplicationRootDefined()) {
            return ${static::CONST_NAME_APPLICATION_ROOT_DIR};
        }

        $vendorPosition = strpos($filePath, 'vendor');

        if ($vendorPosition !== false) {
            return substr($filePath, 0, $vendorPosition);
        }

        $sourcePosition = strpos($filePath, 'src');

        return substr($filePath, 0, $sourcePosition);
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function getProjectPath(string $filePath): string
    {
        $rootPath = $this->getRootApplicationDirectoryPathByFilePath($filePath);

        $path = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $path .= 'src' . DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function getCorePath(string $filePath): string
    {
        $rootPath = $this->getRootApplicationDirectoryPathByFilePath($filePath);
        $corePath = mb_substr($filePath, 0, mb_strpos($filePath, 'src'));
        $corePath = rtrim($corePath, DIRECTORY_SEPARATOR);
        $corePath = explode(DIRECTORY_SEPARATOR, $corePath);

        array_pop($corePath);
        $corePath = implode(DIRECTORY_SEPARATOR, $corePath);

        return rtrim($corePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $moduleName
     * @param \ArchitectureSniffer\Path\Transfer\PathTransfer $pathTransfer
     *
     * @return string
     */
    public function getCoreModulePathByModuleName(string $moduleName, PathTransfer $pathTransfer): string
    {
        $coreModulePattern = implode(DIRECTORY_SEPARATOR, [
            '%1$s',
            'src',
            'Spryker',
            'Zed',
            '%1$s',
        ]);

        return $pathTransfer->getCorePath() . sprintf($coreModulePattern, $moduleName) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $moduleName
     * @param \ArchitectureSniffer\Path\Transfer\PathTransfer $pathTransfer
     *
     * @return string
     */
    public function getProjectModulePathByModuleName(string $moduleName, PathTransfer $pathTransfer): string
    {
        return $pathTransfer->getProjectPath() . $moduleName . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $modulePath
     *
     * @return string
     */
    public function getSchemaPath(string $modulePath): string
    {
        return $modulePath . DIRECTORY_SEPARATOR . static::PATTERN_PATH_MODULE_SCHEMA_FOLDER . DIRECTORY_SEPARATOR;
    }

    /**
     * @return bool
     */
    protected function isApplicationRootDefined(): bool
    {
        return defined(static::CONST_NAME_APPLICATION_ROOT_DIR);
    }
}
