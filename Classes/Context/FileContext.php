<?php
namespace PunktDe\Behat\File\Context;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Behat\Behat\Context\Context;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Neos\Utility\Files;
use PHPUnit\Framework\Assert;
use PunktDe\Behat\Database\Utility\Replacement;

class FileContext implements Context
{
    /**
     * @var string
     */
    protected $featureBasePath;

    /**
     * @var Replacement
     */
    protected $replacement;

    /**
     * @param string $featureBasePath
     * @throws SuiteConfigurationException
     */
    public function __construct($featureBasePath)
    {
        $this->featureBasePath = realpath($featureBasePath);
        if (!is_dir($featureBasePath)) {
            throw new SuiteConfigurationException(sprintf('The basePath %s was not found.', $featureBasePath), 1432736667);
        }
        $this->replacement = new Replacement();
    }


    /**
     * @Given I touch :filePath
     */
    public function touchFile($filePath)
    {
        touch($filePath);
    }

    /**
     * @Then file :filePath exists
     */
    public function fileExists($filePath)
    {
        Assert::assertFileExists($filePath);
    }

    /**
     * @Then file :filePath does not exist
     */
    public function fileDoesNotExist($filePath)
    {
        Assert::assertFileNotExists($filePath);
    }

    /**
     * @Then /^(?:|I )delete file "(?P<filePath>(?:[^"]|\\")*)"(?P<useFeatureBasePath>(?: in featureBasePath))?$/
     */
    public function deleteFile($filePath, $useFeatureBasePath = null)
    {
        if ($useFeatureBasePath !== null) {
            $filePath = Files::concatenatePaths(array($this->featureBasePath, $filePath));
        }
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * @Then I copy file :sourceFilePath to :destinationFilePath
     */
    public function copyFile($sourceFilePath, $destinationFilePath)
    {
        copy($this->buildAbsolutePath($sourceFilePath), $this->buildAbsolutePath($destinationFilePath));
    }

    /**
     * @Then I copy file :sourcePath to :destinationPath from :sourceHost to :destinationHost
     * @Then I copy directory :sourcePath to :destinationPath from :sourceHost to :destinationHost
     */
    public function secureCopyFile($sourcePath, $destinationPath, $sourceHost, $destinationHost)
    {
        $currentUser = posix_getpwuid(posix_geteuid())["name"];
        $command = sprintf("scp -r -o StrictHostKeyChecking=no %s@%s:%s %s@%s:%s",
            $currentUser,
            $sourceHost,
            $this->buildAbsolutePath($sourcePath),
            $currentUser,
            $destinationHost,
            $this->buildAbsolutePath($destinationPath)
        );
        exec($command);
    }

    /**
     * @Then I copy directory :sourceDirectoryPath recursively to :destinationDirectory
     */
    public function copyDirectoryRecursively($sourceDirectory, $destinationDirectory)
    {
        Files::copyDirectoryRecursively($this->buildAbsolutePath($sourceDirectory), $this->buildAbsolutePath($destinationDirectory));
    }

    /**
     * @Then the file :expectedFile is equal to :actualFile
     * @Then the file :expectedFile equals :actualFile
     */
    public function compareFiles($expectedFile, $actualFile)
    {
        $expectedFileContentWithMarkers = file_get_contents($this->buildAbsolutePath($expectedFile));
        $expectedFileContent = $this->replacement->replaceMarkers($expectedFileContentWithMarkers);
        $actualFileContent = file_get_contents($this->buildAbsolutePath($actualFile));
        Assert::assertEquals($expectedFileContent, $actualFileContent);
    }

    /**
     * @Then I create directory :directory
     */
    public function createDirectory($directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * @param string $path
     * @return string
     */
    protected function buildAbsolutePath($path)
    {
        if (strpos($path, DIRECTORY_SEPARATOR) === 0) {
            return $path;
        }
        return Files::concatenatePaths(array($this->featureBasePath, $path));
    }

    /**
     * @Then the file :fileName has the size :expectedSize
     */
    public function checkFileSizeMatches($fileName, $expectedSize)
    {
        $fileSize = filesize(Files::concatenatePaths(array($this->featureBasePath, $fileName)));
        Assert::assertSame(intval($expectedSize), $fileSize, $message = '');
    }


    /**
     * @When I empty directory :folderPath
     */
    public function emptyDirectory($folderPath)
    {
        $folderPath = $this->featureBasePath . '/' . $folderPath;
        if (is_dir($folderPath)) {
            Files::emptyDirectoryRecursively($folderPath);
        }
    }


}
