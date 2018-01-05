<?php
namespace PunktDe\Behat\File\Context;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Behat\Behat\Context\Context;
use Neos\Utility\Files;
use PHPUnit\Framework\Assert;

class XMLTestingContext implements Context
{
    /**
     * @var array
     */
    protected $readableErrorCodes = [
        LIBXML_ERR_WARNING => 'Warning',
        LIBXML_ERR_ERROR => 'Error',
        LIBXML_ERR_FATAL => 'Fatal',
    ];

    /**
     * @var array
     */
    protected $dictionary = [
        'invalid' => false,
        'valid' => true
    ];

    /**
     * @var string
     */
    protected $featureBasePath;

    /**
     * @param string $featureBasePath
     */
    public function __construct($featureBasePath = null)
    {
        $this->featureBasePath = realpath($featureBasePath);
    }

    /**
     * @Then there should be a :xmlTag tag in xml response
     * @Then /^(?:|ich )sollte (?:|ich )den XML Tag "([^"]*)" sehen$/
     *
     * This step can check the content of xml responses in browser.
     * This is needed for browser which are not showing xml structure.
     */
    public function thereShouldBeATagInXmlResponse($xmlTag)
    {
        $respond = $this->getSession()->getDriver()->getContent();

        if (!preg_match("/<?xml(>|\s(.*)>)/", $respond)) {
            $url = $this->getSession()->getCurrentUrl();
            throw new \Exception(sprintf('URL "%s" is not a xml response', $url), 1515121485);
        }

        if (preg_match("/<$xmlTag(>|\s(.*)>)/", $respond)) {
            return true;
        } else {
            throw new \Exception(sprintf('Did not find the tag "%s" in the xml response', $xmlTag), 1515121486);
        }
    }

    /**
     * @Given /^"(?P<xmlFilePath>[^"]+)" is (?P<expectedValidity>(?:valid|invalid)) according to "(?P<xsdFilePath>[^"]+)"$/
     */
    public function xmlIsValidAccordingToXsd($xmlFilePath, $expectedValidity, $xsdFilePath)
    {
        libxml_use_internal_errors(true);

        $absoluteXmlFilePath = Files::concatenatePaths([$this->featureBasePath, $xmlFilePath]);
        $absoluteXsdFilePath = Files::concatenatePaths([$this->featureBasePath, $xsdFilePath]);

        Assert::assertFileExists($absoluteXmlFilePath);
        Assert::assertFileExists($absoluteXsdFilePath);

        $dom = new \DOMDocument();
        $dom->load($absoluteXmlFilePath);

        $actual = false;
        $expected = $this->dictionary[$expectedValidity];

        try {
            $actual = $dom->schemaValidate($absoluteXsdFilePath);
        } catch (\Exception $exception) {
        }

        if ($actual === false && $expected === true) {
            $errors = $this->getErrors();
            $errors[] = sprintf("The XML %s is not valid according to XSD schema %s %s", $absoluteXmlFilePath, $absoluteXsdFilePath, implode(',', $this->getErrors()));
            throw new \Exception(implode("", $errors), 1515121487);
        } elseif ($actual === true && $expected === false) {
            throw new \Exception(sprintf("The XML %s is actually valid according to XSD schema %s", $absoluteXmlFilePath, $absoluteXsdFilePath), 1515121488);
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        $errors = libxml_get_errors();
        $errorLines = [];

        foreach ($errors as $error) {
            $errorLines[] = sprintf('[%s] Line %s: %s', $this->readableErrorCodes[$error->level], $error->line, $error->message);
        }

        libxml_clear_errors();

        return $errorLines;
    }
}
