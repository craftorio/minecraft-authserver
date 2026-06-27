<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Command\Certificates;

use Craftorio\Authserver\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateCommand
 * @package Craftorio\Authserver\Command\Account
 */
class GenerateCommand extends Command
{
    protected $config;

    /**
     * GenerateCommand constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('certificates:generate')
            ->setDescription('Generate new certificates');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \PhpZip\Exception\ZipException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1024-bit RSA + SHA-1 matches legacy Mojang Yggdrasil client expectations.
        // Used by Authenticator::getPropertiesTextures() for sha1WithRSAEncryption signing.
        $key = openssl_pkey_new([
            "digest_alg" => 'sha1',
            "private_key_bits" => 1024,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        $exportDir = $this->config->get('certificatesDir');
        if (is_file($exportDir . DIRECTORY_SEPARATOR . 'yggdrasil_session_private.pem')) {
            $output->writeln("Certificate already exists: " . $exportDir . DIRECTORY_SEPARATOR . 'yggdrasil_session_private.pem');
            return 1;
        }

        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Generate private key
        if (openssl_pkey_export($key, $export)) {
            file_put_contents($exportDir . DIRECTORY_SEPARATOR . 'yggdrasil_session_private.pem', $export);
        } else {
            $output->writeln(openssl_error_string());
            return 1;
        }

        // Generate public pem key
        $details = openssl_pkey_get_details($key);
        file_put_contents($exportDir . DIRECTORY_SEPARATOR . 'yggdrasil_session_public.pem', $details['key']);

        // Strip PEM headers/footers — clients expect raw base64-encoded DER, not PEM text.
        $lines = explode("\n", $details['key']);
        array_shift($lines);
        array_pop($lines);
        array_pop($lines);
        $der = base64_decode(implode('', $lines));

        // Java clients load yggdrasil_session_pubkey.der from this jar as a trust anchor.
        $zipFile = new \PhpZip\ZipFile();
        $zipFile
            ->addFromString('yggdrasil_session_pubkey.der', $der)
            ->saveAsFile($exportDir . DIRECTORY_SEPARATOR . 'yggdrasil_session_pubkey.jar')
            ->close();

        $output->writeln("New certificates saved to: {$exportDir}");

        return 0;
    }
}
