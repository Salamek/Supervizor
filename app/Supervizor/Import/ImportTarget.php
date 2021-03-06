<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Supervizor\Import;

use Kdyby\Doctrine\EntityManager;
use Supervizor\Budget\BudgetItem;
use Supervizor\Budget\BudgetRepository;
use Supervizor\Invoice\Invoice;
use Supervizor\Invoice\InvoiceItem;
use Supervizor\Invoice\InvoiceRepository;
use Supervizor\Supplies\Supplier;
use Supervizor\Supplies\SupplierRepository;
use Supervizor\DI\Importer\IImportTarget;

/**
 * Description of ImportTarget
 *
 * @author Adam Schubert <adam.schubert@sg1-game.net>
 */
class ImportTarget implements IImportTarget
{

    /** @var InvoiceRepository @inject */
    public $invoiceRepository;

    /** @var SupplierRepository @inject */
    public $supplierRepository;

    /** @var BudgetRepository @inject */
    public $budgetRepository;

    /** @var ImportRepository @inject */
    public $importRepository;

    /** @var EntityManager @inject */
    public $entityManager;

    /**
     * @param $identifier
     * @param $supplierCompanyIdentifier
     * @param $name
     * @return Supplier|mixed|null|object
     */
    private function createSupplier($identifier, $supplierCompanyIdentifier, $name)
    {
        $foundSupplier = $this->supplierRepository->findByIdentifier($identifier);
        if ($foundSupplier)
        {
            return $foundSupplier;
        }

        $supplier = new Supplier($identifier, $supplierCompanyIdentifier, $name);
        $this->entityManager->persist($supplier);
        return $supplier;
    }

    /**
     * @param $identifier
     * @param $name
     * @return BudgetItem|mixed|null|object
     */
    private function createBudgetItem($identifier, $name)
    {
        $foundBudgetItem = $this->budgetRepository->findByIdentifier($identifier);
        if ($foundBudgetItem)
        {
            return $foundBudgetItem;
        }

        $budgetItem = new BudgetItem($identifier, $name);
        $this->entityManager->persist($budgetItem);
        return $budgetItem;
    }

    /**
     * @param $budgetItem
     * @param $invoice
     * @param $budgetItemAmount
     */
    private function createInvoiceItem($budgetItem, $invoice, $budgetItemAmount)
    {
        $foundInvoiceItem = $this->invoiceRepository->findItemByInvoiceAndBudgetItem($budgetItem, $invoice);
        if (!$foundInvoiceItem)
        {
            $invoiceItem = new InvoiceItem($invoice, $budgetItem, $budgetItemAmount);
            $this->entityManager->persist($invoiceItem);
        }
    }

    /**
     * @param $identifier
     * @param $type
     * @param $distinction
     * @param $vatRecord
     * @param $amount
     * @param $amountWithoutVat
     * @param $amountOriginal
     * @param $amountPaid
     * @param $amountPaidOriginal
     * @param $currency
     * @param \DateTime $issued
     * @param \DateTime $received
     * @param \DateTime $paid
     * @param \DateTime $maturity
     * @param $description
     * @param $supplierIdentifier
     * @param $supplierName
     * @param $supplierCompanyIdentifier
     * @param $budgetItemIdentifier
     * @param $budgetItemName
     * @param $budgetItemAmount
     * @throws \Exception
     */
    public function setInvoice(
        $identifier,
        $type,
        $distinction,
        $vatRecord,
        $amount,
        $amountWithoutVat,
        $amountOriginal,
        $amountPaid,
        $amountPaidOriginal,
        $currency,
        \DateTime $issued,
        \DateTime $received,
        \DateTime $paid,
        \DateTime $maturity,
        $description,
        $supplierIdentifier,
        $supplierName,
        $supplierCompanyIdentifier,
        $budgetItemIdentifier,
        $budgetItemName,
        $budgetItemAmount,
        $import
    )
    {
        /** @var Invoice $foundInvoice */
        $foundInvoice = $this->invoiceRepository->findByIdentifier($identifier);
        if ($foundInvoice)
        {
            $foundInvoice->setImport($import);
            $invoice = $foundInvoice;
        }
        else
        {
            if ($supplierIdentifier)
            {
                $supplier = $this->createSupplier($supplierIdentifier, $supplierCompanyIdentifier, $supplierName);
            }
            else
            {
                $supplier = null;
            }
            $invoice = new Invoice($import, $identifier, $type, $distinction, $vatRecord, $amount, $amountWithoutVat,
                $amountOriginal, $amountPaid, $amountPaidOriginal, $currency, $issued, $received, $maturity, $paid,
                $description, $supplier);
        }

        $this->entityManager->persist($invoice);

        if (!$budgetItemName)
        {
            $budgetItemName = 'Rozpočtová položka neurčena';

            if (!$budgetItemIdentifier)
            {
                $budgetItemIdentifier = 0;
            }
        }

        if ($budgetItemAmount)
        {
            $budgetItem = $this->createBudgetItem($budgetItemIdentifier, $budgetItemName);
            $this->createInvoiceItem($budgetItem, $invoice, $budgetItemAmount);
        }

        $this->entityManager->flush();
    }

    /**
     * @param $name
     * @param $slug
     * @param $isDefault
     * @return mixed
     */
    public function setImportGroup($name, $slug, $isDefault)
    {
        return $this->importRepository->setImportGroup($name, $slug, $isDefault);
    }

    /**
     * @param $importGroup
     * @param $name
     * @param $slug
     * @param $description
     * @param $homepage
     * @param $isDefault
     * @return mixed
     */
    public function setImport($importGroup, $name, $slug, $description, $homepage, $isDefault)
    {
        return $this->importRepository->setImport($importGroup, $name, $slug, $description, $homepage, $isDefault);
    }

}
